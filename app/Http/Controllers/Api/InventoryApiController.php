<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\inventory\Inventory;
use App\Models\inventory\InventoryActivity;
use App\Models\products\Product;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class InventoryApiController extends Controller
{
    /**
     * Map one or many EPC tags to a product. (From Hand Reader)
     *
     * Accepts both:
     *  - Compact: { product_id: 12, epc_codes: ["E1","E2"] }
     *  - Legacy:  { mappings: [ { epc_code, product_id, ... }, ... ] }
     */
    public function tagMapping(Request $request)
    {
        $payload = $request->all();
        Log::info('tagMapping raw payload: '.json_encode($payload));

        // Build unified mapping array
        $mappings = [];

        // New compact format
        if (! empty($payload['product_id']) && ! empty($payload['epc_codes']) && is_array($payload['epc_codes'])) {
            foreach ($payload['epc_codes'] as $epc) {
                $epc = trim($epc);
                if (! $epc) {
                    continue;
                }

                $mappings[] = [
                    'product_id' => $payload['product_id'],
                    'epc_code' => $epc,
                    'tag_code' => $payload['tag_code'] ?? null,
                    'trolley_id' => $payload['trolley_id'] ?? null,
                ];
            }
        }

        // Legacy format
        if (! empty($payload['mappings']) && is_array($payload['mappings'])) {
            foreach ($payload['mappings'] as $m) {
                $epc = trim($m['epc_code'] ?? '');
                if (! $epc) {
                    continue;
                }

                $mappings[] = [
                    'product_id' => $m['product_id'] ?? null,
                    'epc_code' => $epc,
                    'tag_code' => $m['tag_code'] ?? null,
                    'trolley_id' => $m['trolley_id'] ?? null,
                ];
            }
        }

        if (empty($mappings)) {
            return response()->json([
                'success' => false,
                'message' => 'No valid mappings provided',
            ], 400);
        }

        $results = [];
        $seenEpcs = [];
        $userId = Auth::id();

        DB::beginTransaction();
        try {
            foreach ($mappings as $map) {

                $epc = $map['epc_code'];

                // Skip duplicate EPC in same request
                if (isset($seenEpcs[$epc])) {
                    $results[] = [
                        'epc_code' => $epc,
                        'success' => false,
                        'message' => 'Duplicate epc in request',
                    ];

                    continue;
                }
                $seenEpcs[$epc] = true;

                // Validate product
                $product = Product::find($map['product_id']);
                if (! $product) {
                    $results[] = [
                        'epc_code' => $epc,
                        'success' => false,
                        'message' => 'Product not found',
                    ];

                    continue;
                }

                $locationId = $product->location_id;

                // 🚫 CHECK IF TAG ALREADY EXISTS → BLOCK REMAPPING
                $existingTag = Inventory::where('epc_code', $epc)->first();
                if ($existingTag) {
                    $results[] = [
                        'epc_code' => $epc,
                        'tag_id' => $existingTag->id,
                        'product_id' => $existingTag->product_id,
                        'success' => false,
                        'message' => 'Tag already mapped — cannot be remapped or replaced',
                    ];

                    continue;
                }

                // ✅ CREATE NEW TAG (Only case allowed)
                $tag = Inventory::create([
                    'epc_code' => $epc,
                    'tag_code' => $map['tag_code'],
                    'product_id' => $product->id,
                    'location_id' => $locationId,
                    'trolley_id' => $map['trolley_id'],
                    'status' => 'new',
                    'mapped_at' => Carbon::now(),
                    'last_scanned_at' => Carbon::now(),
                    'life_cycles' => $product->expected_life_cycles ?? 1000,
                ]);

                // Create movement record (only INWARD)
                $activity = InventoryActivity::create([
                    'product_id' => $product->id,
                    'inventory_id' => $tag->id,
                    'adjust_qty' => 1,
                    'inward' => 1,
                    'outward' => 0,
                    'trans_type' => 'tag_mapping',
                    'remarks' => 'Tag created & assigned',
                    'status' => 'new',
                    'location_id' => $locationId,
                    'created_by' => $userId,
                    'updated_by' => $userId,
                ]);

                $results[] = [
                    'epc_code' => $epc,
                    'tag_id' => $tag->id,
                    'product_id' => $product->id,
                    'location_id' => $locationId,
                    'success' => true,
                    'message' => 'Tag created',
                    'movement' => ['assign' => $activity->toArray()],
                ];
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'results' => $results,
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('tagMapping error: '.$e->getMessage(), ['exception' => $e]);

            return response()->json([
                'success' => false,
                'message' => 'Mapping failed: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Record a single inventory transaction (inward/outward/transfer/washing/etc).
     *
     * This endpoint remains useful for manual/external calls. It validates input,
     * creates an InventoryActivity row (with opening/closing computed by model),
     * and updates the tag if provided.
     */
    public function recordStockMovement(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|exists:products,id',
            'inventory_id' => 'nullable|exists:rfid_tags,id',
            'trans_type' => 'required|string|max:100',
            'inward' => 'nullable|integer|min:0',
            'outward' => 'nullable|integer|min:0',
            'adjust_qty' => 'nullable|integer',
            'location_id' => 'nullable|integer',
            'remarks' => 'nullable|string|max:255',
            'update_tag_status' => 'nullable|string',   // clean / dirty / out / lost / damaged
            'status' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $userId = Auth::id();

        DB::beginTransaction();
        try {
            $productId = intval($request->product_id);
            $inventoryId = $request->inventory_id ? intval($request->inventory_id) : null;
            $inward = intval($request->inward ?? 0);
            $outward = intval($request->outward ?? 0);

            $adjustQty = $request->filled('adjust_qty')
                ? intval($request->adjust_qty)
                : ($inward > 0 ? $inward : ($outward > 0 ? $outward : 0));

            $type = $request->trans_type;
            $remarks = $request->remarks ?? null;
            $locationId = $request->location_id ?? null;
            $status = $request->has('status') ? intval($request->status) : 1;

            // Create Inventory Activity
            $activity = InventoryActivity::create([
                'product_id' => $productId,
                'inventory_id' => $inventoryId,
                'adjust_qty' => $adjustQty,
                'inward' => $inward,
                'outward' => $outward,
                'trans_type' => $type,
                'remarks' => $remarks,
                'status' => $status,
                'location_id' => $locationId,
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);

            // Update tag status if tag exists
            if ($inventoryId) {
                $tag = Inventory::find($inventoryId);

                if ($tag) {
                    $updateTag = [];

                    /** ----------------------------
                     * 🚦 STATUS CYCLE LOGIC HERE
                     * -----------------------------
                     * new → clean
                     * clean → dirty
                     * dirty → clean
                     */
                    if ($request->filled('update_tag_status')) {

                        $current = $tag->status;
                        $input = $request->get('update_tag_status');

                        if ($current === 'new') {
                            $updateTag['status'] = 'clean';
                        } elseif ($current === 'clean') {
                            $updateTag['status'] = 'dirty';
                        } elseif ($current === 'dirty') {
                            $updateTag['status'] = 'clean';
                        } else {
                            // override for special cases (out / lost / damaged)
                            $updateTag['status'] = $input;
                        }
                    }

                    // update location if given
                    if (! is_null($locationId)) {
                        $updateTag['location_id'] = $locationId;
                    }

                    $updateTag['last_scanned_at'] = Carbon::now();

                    if (! empty($updateTag)) {
                        $tag->update($updateTag);
                    }
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Movement recorded',
                'data' => $activity,
            ], 201);

        } catch (\Throwable $e) {

            DB::rollBack();
            Log::error('recordStockMovement error: '.$e->getMessage(), ['exception' => $e]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to record movement: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Fetch full details of an EPC tag: inventory record, mapped product,
     * and last 10 inventory activities.
     */
    public function tagDetailsByEpc($epc)
    {
        Log::info('Fetching tag details for EPC: '.$epc);
        try {
            $epc = trim($epc);

            // Load tag + product relation safely
            $tag = Inventory::with('products')
                ->where('epc_code', $epc)
                ->first();

            if (! $tag) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tag Not Mapped!!',
                    'inventory' => null,
                    'product' => null,
                    'history' => [],
                ], 200);
            }

            // Last 10 activity history
            $history = InventoryActivity::where('inventory_id', $tag->id)
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get();

            // Ensure product exists — avoid null errors on Android
            $product = $tag->products ?? null;

            // Convert status integer to readable text
            $statusText = match ((int) $tag->status) {
                1 => 'Active',
                0 => 'Inactive',
                default => 'Unknown',
            };
            $reponseData = [
                'success' => true,
                'message' => 'Tag details loaded',

                // Inventory Tag Details
                'inventory' => [
                    'id' => $tag->id,
                    'epc_code' => $tag->epc_code,
                    'tag_code' => $tag->tag_code,
                    'product_id' => $tag->product_id,
                    'location_id' => $tag->location_id,
                    'status' => $tag->status,
                    'status_text' => $statusText,
                    'last_scanned_at' => $tag->last_scanned_at,
                    'mapped_at' => $tag->mapped_at,
                    'product_name' => $product->product_name ?? '',
                    'product_code' => $product->product_code ?? '',
                    'category' => $product->category ?? '',
                    'sku' => $product->sku ?? '',
                ],

                // Product Details
                // 'product' => $product ? [
                //     'id' => $product->id,
                //     'product_name' => $product->product_name ?? '',
                //     'product_code' => $product->product_code ?? '',
                //     'sku' => $product->sku ?? '',
                //     'category' => $product->category ?? '',
                // ] : null,

                // Last 10 movements
                // 'history' => $history,

            ];
            Log::info('tagDetailsByEpc response: '.json_encode($reponseData));

            return response()->json($reponseData, 200);

        } catch (\Throwable $e) {
            Log::error('tagDetailsByEpc error: '.$e->getMessage(), ['exception' => $e]);

            return response()->json([
                'success' => false,
                'message' => 'Server error: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Receive scans from a fixed reader device (multiple tags).
     *
     * Payload:
     * {
     *   "reader_code": "GATE_A_01",
     *   "movement": "inward",           // optional, default = inward (allowed: inward|outward)
     *   "reads": [
     *     {"epc":"E2003412ABC...", "read_time":"2025-12-09 10:12:55", "qty":1, "movement":"outward"},
     *     {"epc":"E2003412DEF...", "read_time":"2025-12-09 10:12:56"}
     *   ]
     * }
     */
    // public function fixedReaderScan(Request $request)
    // {
    //     Log::info('fixedReaderScan called'.json_encode($request->all()));
    //     $validator = Validator::make($request->all(), [
    //         'reader_code' => 'required|string|max:100',
    //         'movement' => 'nullable|in:inward,outward',
    //         'reads' => 'required|array|min:1',
    //         'reads.*.epc' => 'required|string',
    //         'reads.*.read_time' => 'nullable|date',
    //         'reads.*.qty' => 'nullable|integer|min:1',
    //         'reads.*.movement' => 'nullable|in:inward,outward',
    //     ]);

    //     if ($validator->fails()) {
    //         return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
    //     }

    //     $payload = $request->all();
    //     Log::info('fixedReaderScan payload: '.json_encode($payload));

    //     $reads = $payload['reads'];
    //     $readerCode = $payload['reader_code'];
    //     $globalMovement = 'outward'; // $payload['movement'] ?? 'inward'; // default movement

    //     // Hard-coded user id for created_by / updated_by (route is public).
    //     // Uses env value FIXED_READER_USER_ID if set, otherwise falls back to 2.
    //     $userId = 3; // intval(env('FIXED_READER_USER_ID', 2));

    //     $now = Carbon::now();

    //     $results = [];
    //     $seen = [];

    //     DB::beginTransaction();
    //     try {
    //         foreach ($reads as $r) {

    //             $epc = trim($r['epc'] ?? '');
    //             if ($epc === '') {
    //                 $results[] = [
    //                     'epc' => null,
    //                     'success' => false,
    //                     'message' => 'Empty EPC provided',
    //                 ];

    //                 continue;
    //             }

    //             // skip duplicates in same request
    //             if (isset($seen[$epc])) {
    //                 $results[] = [
    //                     'epc' => $epc,
    //                     'success' => false,
    //                     'message' => 'Duplicate EPC in payload - skipped',
    //                 ];

    //                 continue;
    //             }
    //             $seen[$epc] = true;

    //             // find tag
    //             $tag = Inventory::where('epc_code', $epc)->first();

    //             if (! $tag) {
    //                 $results[] = [
    //                     'epc' => $epc,
    //                     'success' => false,
    //                     'message' => 'Tag not mapped',
    //                 ];

    //                 continue;
    //             }

    //             // determine read time
    //             $readTime = $now;
    //             if (! empty($r['read_time'])) {
    //                 try {
    //                     $readTime = Carbon::parse($r['read_time']);
    //                 } catch (\Throwable $e) {
    //                     $readTime = $now;
    //                 }
    //             }

    //             // update tag record: last_scanned_at, reader_code, reader_type
    //             $tag->update([
    //                 'last_scanned_at' => $readTime,
    //                 'reader_code' => $readerCode,
    //                 'reader_type' => 'fixed_reader',
    //             ]);

    //             // decide movement and qty: per-read overrides global
    //             $movement = $r['movement'] ?? $globalMovement ?? 'inward';
    //             $qty = isset($r['qty']) ? intval($r['qty']) : 1;
    //             if ($qty <= 0) {
    //                 $qty = 1;
    //             }

    //             // If tag has no mapped product, skip creating InventoryActivity
    //             if (empty($tag->product_id)) {
    //                 $results[] = [
    //                     'epc' => $epc,
    //                     'tag_id' => $tag->id,
    //                     'product_id' => null,
    //                     'location_id' => $tag->location_id,
    //                     'success' => false,
    //                     'message' => 'Tag has no product mapping - movement skipped',
    //                 ];

    //                 continue;
    //             }

    //             // Prepare inward/outward fields
    //             $inward = $movement === 'inward' ? $qty : 0;
    //             $outward = $movement === 'outward' ? $qty : 0;
    //             $transType = $movement === 'inward' ? 'fixed_reader_inward' : 'fixed_reader_outward';

    //             // create inventory activity (stock movement)
    //             $activity = InventoryActivity::create([
    //                 'product_id' => $tag->product_id,
    //                 'inventory_id' => $tag->id,
    //                 'adjust_qty' => $qty,
    //                 'inward' => $inward,
    //                 'outward' => $outward,
    //                 'trans_type' => $transType,
    //                 'remarks' => 'Scanned by fixed reader: '.$readerCode,
    //                 'status' => 1,
    //                 'location_id' => $tag->location_id,
    //                 'created_by' => $userId,
    //                 'updated_by' => $userId,
    //             ]);

    //             $results[] = [
    //                 'epc' => $epc,
    //                 'tag_id' => $tag->id,
    //                 'product_id' => $tag->product_id,
    //                 'location_id' => $tag->location_id,
    //                 'success' => true,
    //                 'message' => 'Scan recorded and movement logged',
    //                 'movement' => $movement,
    //                 'qty' => $qty,
    //                 'activity_id' => $activity->trans_id ?? null,
    //             ];
    //         }

    //         DB::commit();

    //         // overall success if any true
    //         $hasSuccess = collect($results)->contains(fn ($r) => ! empty($r['success']) && $r['success'] === true);

    //         return response()->json([
    //             'success' => $hasSuccess,
    //             'message' => $hasSuccess ? 'Scans recorded' : 'All reads failed or were skipped',
    //             'results' => $results,
    //         ], 200);

    //     } catch (\Throwable $e) {

    //         DB::rollBack();
    //         Log::error('fixedReaderScan error: '.$e->getMessage(), ['exception' => $e]);

    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Server error: '.$e->getMessage(),
    //         ], 500);
    //     }
    // }

    public function fixedReaderScan(Request $request)
    {
        Log::info('fixedReaderScan called '.json_encode($request->all()));

        // Validation
        $validator = Validator::make($request->all(), [
            'reader_code' => 'required|string|max:100',
            'reads' => 'required|array|min:1',
            'reads.*.epc' => 'required|string',
            'reads.*.read_time' => 'nullable|date',
            'reads.*.qty' => 'nullable|integer|min:1',
            // optional control:
            'defaultMovementAction' => 'nullable|string|in:clean,dirty',
            'min_scan_interval' => 'nullable|integer|min:0', // seconds
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $payload = $request->all();
        Log::info('fixedReaderScan payload: '.json_encode($payload));

        $reads = $payload['reads'];
        $readerCode = $payload['reader_code'];

        // Optional control: set via request or fallback to null (process both)
        $defaultMovementAction = 'dirty'; // clean or dirty//isset($payload['defaultMovementAction']) ? strtolower($payload['defaultMovementAction']) : null;
        // Optional: min debounce interval (seconds). Default 5 seconds if not provided.
        $minScanIntervalSeconds = isset($payload['min_scan_interval']) ? intval($payload['min_scan_interval']) : 5;

        $userId = auth()->id() ?? 3; // prefer real auth; fallback to 3 if not available
        $now = Carbon::now();
        $results = [];
        $seen = []; // per-request duplicate filter

        DB::beginTransaction();
        try {

            foreach ($reads as $r) {

                $epc = trim($r['epc'] ?? '');

                if ($epc === '') {
                    $results[] = ['epc' => null, 'success' => false, 'message' => 'Empty EPC'];

                    continue;
                }

                // Skip duplicates in same payload
                if (isset($seen[$epc])) {
                    $results[] = ['epc' => $epc, 'success' => false, 'message' => 'Duplicate EPC - skipped'];

                    continue;
                }
                $seen[$epc] = true;

                // Determine read time
                $readTime = $now;
                if (! empty($r['read_time'])) {
                    try {
                        $readTime = Carbon::parse($r['read_time']);
                    } catch (\Throwable $e) {
                        $readTime = $now;
                    }
                }

                // Raw log of the read for traceability
                try {
                    DB::table('rfid_read_events')->insert([
                        'epc_code' => $epc,
                        'reader_id' => $readerCode,
                        'rssi' => $r['rssi'] ?? null,
                        'read_time' => $readTime,
                        'created_at' => Carbon::now(),
                        'updated_at' => Carbon::now(),
                    ]);
                } catch (\Throwable $e) {
                    // don't fail entire process if logging fails; just warn
                    Log::warning("rfid_read_events insert failed for {$epc}: ".$e->getMessage());
                }

                // Find tag (inventory/master)
                $tag = Inventory::where('epc_code', $epc)->first();
                if (! $tag) {
                    $results[] = ['epc' => $epc, 'success' => false, 'message' => 'Tag not mapped'];

                    continue;
                }

                // Debounce: ignore scans that happen too soon after last_scanned_at
                if (! empty($tag->last_scanned_at)) {
                    try {
                        $last = Carbon::parse($tag->last_scanned_at);
                        $diffSec = $readTime->diffInSeconds($last);
                        if ($diffSec < $minScanIntervalSeconds) {
                            $results[] = [
                                'epc' => $epc,
                                'success' => false,
                                'message' => "Ignored: scanned {$diffSec}s after previous (min {$minScanIntervalSeconds}s).",
                            ];

                            continue;
                        }
                    } catch (\Throwable $e) {
                        // if parse fails, ignore debounce
                    }
                }

                // -----------------------
                // Status cycle map
                // -----------------------
                $currentStatus = strtolower($tag->status ?? 'new'); // default to new if null
                $cycle = [
                    'new' => 'clean',
                    'clean' => 'dirty',
                    'dirty' => 'clean',
                ];
                $nextStatus = $cycle[$currentStatus] ?? null;

                // If the user requested only "clean" or only "dirty" processing:
                // - allow the status-change only if target nextStatus matches the requested action
                if ($defaultMovementAction !== null && $nextStatus !== null) {
                    if ($nextStatus !== $defaultMovementAction) {
                        // Skip this tag — user only wants the other action
                        $results[] = [
                            'epc' => $epc,
                            'success' => false,
                            'message' => "Skipped by defaultMovementAction={$defaultMovementAction}. Next status would be {$nextStatus}.",
                            'current_status' => $currentStatus,
                        ];

                        continue;
                    }
                }

                // -----------------------
                // HANDLE STATUS TRANSITION (if any)
                // -----------------------
                if ($nextStatus !== null && $nextStatus !== $currentStatus) {

                    // Life cycle: decrement ONLY when DIRTY -> CLEAN (wash completed)
                    $lifeCycles = $tag->life_cycles;
                    if ($currentStatus === 'dirty' && $nextStatus === 'clean' && is_numeric($lifeCycles)) {
                        $lifeCycles = max(0, intval($lifeCycles) - 1);
                    }

                    // Update tag record with new status and last scan meta
                    $tag->update([
                        'status' => $nextStatus,
                        'life_cycles' => $lifeCycles,
                        'last_scanned_at' => $readTime,
                        'reader_code' => $readerCode,
                        'reader_type' => 'fixed_reader',
                        'updated_by' => $userId,
                    ]);

                    // Determine automatic movement triggered by this transition
                    // dirty -> clean => outward (clean item leaving)
                    // clean -> dirty => inward (dirty item arriving)
                    $autoMovement = null;
                    if ($currentStatus === 'dirty' && $nextStatus === 'clean') {
                        $autoMovement = 'outward';
                    } elseif ($currentStatus === 'clean' && $nextStatus === 'dirty') {
                        $autoMovement = 'inward';
                    }

                    // Only create InventoryActivity if product mapping exists and movement determined
                    if (! empty($tag->product_id) && $autoMovement !== null) {
                        $qty = max(1, intval($r['qty'] ?? 1));

                        $inward = $autoMovement === 'inward' ? $qty : 0;
                        $outward = $autoMovement === 'outward' ? $qty : 0;

                        $transType = $autoMovement === 'inward' ? 'status_dirty_in' : 'status_clean_out';

                        $activity = InventoryActivity::create([
                            'product_id' => $tag->product_id,
                            'inventory_id' => $tag->id,
                            'adjust_qty' => $qty,
                            'inward' => $inward,
                            'outward' => $outward,
                            'trans_type' => $transType,
                            'remarks' => "Auto movement due to status transition {$currentStatus}→{$nextStatus}",
                            'status' => $nextStatus,
                            'location_id' => $tag->location_id,
                            'created_by' => $userId,
                            'updated_by' => $userId,
                        ]);
                    }

                    $results[] = [
                        'epc' => $epc,
                        'tag_id' => $tag->id,
                        'product_id' => $tag->product_id,
                        'location_id' => $tag->location_id,
                        'success' => true,
                        'message' => "Status changed: {$currentStatus} → {$nextStatus}",
                        'status' => $nextStatus,
                        'movement' => $autoMovement,
                    ];

                    continue; // skip the "no status change" movement block
                }

                // -----------------------
                // NO STATUS CHANGE: derive movement from CURRENT status (if allowed)
                // -----------------------
                $qty = max(1, intval($r['qty'] ?? 1));

                if (empty($tag->product_id)) {
                    $results[] = [
                        'epc' => $epc,
                        'success' => false,
                        'message' => 'Tag has no product mapping - movement skipped',
                    ];

                    continue;
                }

                // Movement from current status: clean => outward, dirty => inward, new => none
                $movement = null;
                if ($currentStatus === 'clean') {
                    $movement = 'outward';
                } elseif ($currentStatus === 'dirty') {
                    $movement = 'inward';
                } else {
                    $movement = null; // new or unknown -> no automatic movement
                }

                // If defaultMovementAction is set, enforce that only movements matching desired target are allowed.
                // For movement derived from current status:
                // - If movement == 'outward', target status would be 'clean' (outward implies previously dirty->clean?), but here movement derived from current ->
                // We interpret the user's intent as: if they chose 'clean', they want transitions/movements that produce status 'clean'.
                if ($defaultMovementAction !== null) {
                    // Allow movement only if it corresponds to action:
                    // movement 'outward' corresponds to resulting NEXT status 'clean'
                    // movement 'inward' corresponds to resulting NEXT status 'dirty'
                    $movementTarget = $movement === 'outward' ? 'clean' : ($movement === 'inward' ? 'dirty' : null);
                    if ($movementTarget !== $defaultMovementAction) {
                        $results[] = [
                            'epc' => $epc,
                            'success' => false,
                            'message' => "Skipped by defaultMovementAction={$defaultMovementAction} for movement {$movement}.",
                        ];

                        continue;
                    }
                }

                // Update last scan metadata
                $tag->update([
                    'last_scanned_at' => $readTime,
                    'reader_code' => $readerCode,
                    'reader_type' => 'fixed_reader',
                    'updated_by' => $userId,
                ]);

                if ($movement !== null) {
                    $inward = $movement === 'inward' ? $qty : 0;
                    $outward = $movement === 'outward' ? $qty : 0;

                    $transType = $movement === 'inward' ? 'fixed_reader_in' : 'fixed_reader_out';

                    $activity = InventoryActivity::create([
                        'product_id' => $tag->product_id,
                        'inventory_id' => $tag->id,
                        'adjust_qty' => $qty,
                        'inward' => $inward,
                        'outward' => $outward,
                        'trans_type' => $transType,
                        'remarks' => "Scanned by fixed reader: {$readerCode}",
                        'status' => $tag->status,
                        'location_id' => $tag->location_id,
                        'created_by' => $userId,
                        'updated_by' => $userId,
                    ]);

                    $results[] = [
                        'epc' => $epc,
                        'success' => true,
                        'message' => 'Movement applied based on status',
                        'movement' => $movement,
                        'qty' => $qty,
                        'activity_id' => $activity->trans_id ?? null,
                    ];
                } else {
                    $results[] = [
                        'epc' => $epc,
                        'success' => true,
                        'message' => "Status is '{$currentStatus}' → no movement",
                    ];
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Scan processed',
                'results' => $results,
            ], 200);

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('fixedReaderScan error: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Server error: '.$e->getMessage(),
            ], 500);
        }
    }
}
