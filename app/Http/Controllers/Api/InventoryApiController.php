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
        $userId = Auth::id();
        $seenEpcs = [];

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

                // Validate product ID
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

                // Existing tag?
                $tag = Inventory::where('epc_code', $epc)->first();
                $isNewTag = false;
                $previousProductId = null;

                // If tag already mapped to same product → skip
                if ($tag && intval($tag->product_id) === intval($product->id)) {
                    $results[] = [
                        'epc_code' => $epc,
                        'tag_id' => $tag->id,
                        'product_id' => $product->id,
                        'success' => false,
                        'message' => 'Already mapped to same product',
                    ];

                    continue;
                }

                // CREATE NEW TAG
                if (! $tag) {
                    $tag = Inventory::create([
                        'epc_code' => $epc,
                        'tag_code' => $map['tag_code'],
                        'product_id' => $product->id,
                        'location_id' => $locationId,
                        'trolley_id' => $map['trolley_id'],
                        'status' => 'new',                   // ALWAYS new
                        'mapped_at' => Carbon::now(),
                        'last_scanned_at' => Carbon::now(),
                    ]);
                    $isNewTag = true;
                }

                // UPDATE existing tag (reassignment)
                else {
                    $previousProductId = $tag->product_id;

                    $tag->update([
                        'product_id' => $product->id,
                        'tag_code' => $map['tag_code'] ?? $tag->tag_code,
                        'location_id' => $locationId,
                        'trolley_id' => $map['trolley_id'] ?? $tag->trolley_id,
                        // status remains SAME — DO NOT CHANGE HERE
                        'last_scanned_at' => Carbon::now(),
                    ]);
                }

                // MOVEMENTS
                $movementInfo = [];

                if ($isNewTag) {
                    // New tag → INWARD
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
                    $movementInfo['assign'] = $activity->toArray();
                }

                // REASSIGN TAG
                elseif ($previousProductId !== $product->id) {

                    // OUT from previous product
                    $outAct = InventoryActivity::create([
                        'product_id' => $previousProductId,
                        'inventory_id' => $tag->id,
                        'adjust_qty' => 1,
                        'inward' => 0,
                        'outward' => 1,
                        'trans_type' => 'tag_reassign_out',
                        'remarks' => 'Tag removed from previous product',
                        'status' => 'new',
                        'location_id' => $locationId,
                        'created_by' => $userId,
                        'updated_by' => $userId,
                    ]);
                    $movementInfo['reassign_out'] = $outAct->toArray();

                    // IN to new product
                    $inAct = InventoryActivity::create([
                        'product_id' => $product->id,
                        'inventory_id' => $tag->id,
                        'adjust_qty' => 1,
                        'inward' => 1,
                        'outward' => 0,
                        'trans_type' => 'tag_reassign_in',
                        'remarks' => 'Tag assigned to new product',
                        'status' => 'new',
                        'location_id' => $locationId,
                        'created_by' => $userId,
                        'updated_by' => $userId,
                    ]);
                    $movementInfo['reassign_in'] = $inAct->toArray();
                }

                $results[] = [
                    'epc_code' => $epc,
                    'tag_id' => $tag->id,
                    'product_id' => $product->id,
                    'location_id' => $locationId,
                    'success' => true,
                    'message' => $isNewTag ? 'Tag created' : 'Tag reassigned',
                    'movement' => $movementInfo,
                ];
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'results' => $results,
            ], 200);

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

        $validator = Validator::make($request->all(), [
            'reader_code' => 'required|string|max:100',
            'movement' => 'nullable|in:inward,outward',
            'reads' => 'required|array|min:1',
            'reads.*.epc' => 'required|string',
            'reads.*.read_time' => 'nullable|date',
            'reads.*.qty' => 'nullable|integer|min:1',
            'reads.*.movement' => 'nullable|in:inward,outward',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $payload = $request->all();
        Log::info('fixedReaderScan payload: '.json_encode($payload));

        $reads = $payload['reads'];
        $readerCode = $payload['reader_code'];

        // Default movement used ONLY when inventory movement is required
        $globalMovement = $payload['movement'] ?? 'inward';

        // Hard-coded user ID
        $userId = 3;

        $now = Carbon::now();
        $results = [];
        $seen = [];

        DB::beginTransaction();
        try {
            foreach ($reads as $r) {

                $epc = trim($r['epc'] ?? '');
                if ($epc === '') {
                    $results[] = [
                        'epc' => null,
                        'success' => false,
                        'message' => 'Empty EPC provided',
                    ];

                    continue;
                }

                // Prevent duplicate EPCs within same request
                if (isset($seen[$epc])) {
                    $results[] = [
                        'epc' => $epc,
                        'success' => false,
                        'message' => 'Duplicate EPC in payload - skipped',
                    ];

                    continue;
                }
                $seen[$epc] = true;

                // Find tag
                $tag = Inventory::where('epc_code', $epc)->first();

                if (! $tag) {
                    $results[] = [
                        'epc' => $epc,
                        'success' => false,
                        'message' => 'Tag not mapped',
                    ];

                    continue;
                }

                // determine read time
                $readTime = $now;
                if (! empty($r['read_time'])) {
                    try {
                        $readTime = Carbon::parse($r['read_time']);
                    } catch (\Throwable $e) {
                        $readTime = $now;
                    }
                }

                // -----------------------------------------------------------------------------------------
                // STATUS FLOW LOGIC
                // new → clean → dirty → clean → dirty ...
                // NO INVENTORY MOVEMENT IS RECORDED FOR STATUS CYCLING
                // -----------------------------------------------------------------------------------------

                $currentStatus = $tag->status;
                $nextStatus = null;

                if ($currentStatus === 'new') {
                    $nextStatus = 'clean';
                } elseif ($currentStatus === 'clean') {
                    $nextStatus = 'dirty';
                } elseif ($currentStatus === 'dirty') {
                    $nextStatus = 'clean';
                }

                if ($nextStatus !== null) {

                    // Update status only
                    $tag->update([
                        'status' => $nextStatus,
                        'last_scanned_at' => $readTime,
                        'reader_code' => $readerCode,
                        'reader_type' => 'fixed_reader',
                    ]);

                    $results[] = [
                        'epc' => $epc,
                        'tag_id' => $tag->id,
                        'product_id' => $tag->product_id,
                        'location_id' => $tag->location_id,
                        'success' => true,
                        'message' => "Status changed: {$currentStatus} → {$nextStatus}",
                        'status' => $nextStatus,
                    ];

                    // SKIP movement creation entirely
                    continue;
                }

                // -----------------------------------------------------------------------------------------
                // MOVEMENT LOGIC (ONLY RUNS WHEN NO STATUS CHANGE IS DONE)
                // -----------------------------------------------------------------------------------------

                $movement = $r['movement'] ?? $globalMovement;
                $qty = isset($r['qty']) ? intval($r['qty']) : 1;
                if ($qty <= 0) {
                    $qty = 1;
                }

                // If no product mapped, skip
                if (empty($tag->product_id)) {
                    $results[] = [
                        'epc' => $epc,
                        'tag_id' => $tag->id,
                        'product_id' => null,
                        'location_id' => $tag->location_id,
                        'success' => false,
                        'message' => 'Tag has no product mapping - movement skipped',
                    ];

                    continue;
                }

                // update tag before movement
                $tag->update([
                    'last_scanned_at' => $readTime,
                    'reader_code' => $readerCode,
                    'reader_type' => 'fixed_reader',
                ]);

                $inward = $movement === 'inward' ? $qty : 0;
                $outward = $movement === 'outward' ? $qty : 0;

                $transType = $movement === 'inward'
                    ? 'fixed_reader_inward'
                    : 'fixed_reader_outward';

                // Create inventory activity entry
                $activity = InventoryActivity::create([
                    'product_id' => $tag->product_id,
                    'inventory_id' => $tag->id,
                    'adjust_qty' => $qty,
                    'inward' => $inward,
                    'outward' => $outward,
                    'trans_type' => $transType,
                    'remarks' => 'Scanned by fixed reader: '.$readerCode,
                    'status' => 1,
                    'location_id' => $tag->location_id,
                    'created_by' => $userId,
                    'updated_by' => $userId,
                ]);

                $results[] = [
                    'epc' => $epc,
                    'tag_id' => $tag->id,
                    'product_id' => $tag->product_id,
                    'location_id' => $tag->location_id,
                    'success' => true,
                    'message' => 'Scan recorded with movement',
                    'movement' => $movement,
                    'qty' => $qty,
                    'activity_id' => $activity->trans_id ?? null,
                ];
            }

            DB::commit();

            $hasSuccess = collect($results)->contains(fn ($r) => $r['success'] === true);

            return response()->json([
                'success' => $hasSuccess,
                'message' => $hasSuccess ? 'Scans recorded' : 'All scans failed',
                'results' => $results,
            ], 200);

        } catch (\Throwable $e) {

            DB::rollBack();
            Log::error('fixedReaderScan error: '.$e->getMessage(), ['exception' => $e]);

            return response()->json([
                'success' => false,
                'message' => 'Server error: '.$e->getMessage(),
            ], 500);
        }
    }
}
