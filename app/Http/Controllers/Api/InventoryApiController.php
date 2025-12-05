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
     * Map one or many EPC tags to a product.
     *
     * Accepts both:
     *  - Compact: { product_id: 12, epc_codes: ["E1","E2"] }
     *  - Legacy:  { mappings: [ { epc_code, product_id, ... }, ... ] }
     */
    public function tagMapping(Request $request)
    {
        $payload = $request->all();
        Log::info('tagMapping raw payload: '.json_encode($payload));

        // Build unified mappings array
        $mappings = [];

        // Compact form: { product_id, epc_codes: [...] }
        if (! empty($payload['product_id']) && ! empty($payload['epc_codes']) && is_array($payload['epc_codes'])) {
            $productIdTop = $payload['product_id'];
            foreach ($payload['epc_codes'] as $epc) {
                if (! is_string($epc)) {
                    continue;
                }
                $epc = trim($epc);
                if ($epc === '') {
                    continue;
                }

                $mappings[] = [
                    'product_id' => $productIdTop,
                    'epc_code' => $epc,
                    'tag_code' => $payload['tag_code'] ?? null,
                    'trolley_id' => $payload['trolley_id'] ?? null,
                    'status' => $payload['status'] ?? null,
                    'last_scanned_at' => $payload['last_scanned_at'] ?? null,
                ];
            }
        }

        // Legacy form
        if (isset($payload['mappings']) && is_array($payload['mappings'])) {
            foreach ($payload['mappings'] as $m) {
                if (! is_array($m)) {
                    continue;
                }
                if (empty($m['epc_code'])) {
                    continue;
                }

                $mappings[] = [
                    'product_id' => $m['product_id'] ?? null,
                    'epc_code' => trim($m['epc_code']),
                    'tag_code' => $m['tag_code'] ?? null,
                    'trolley_id' => $m['trolley_id'] ?? null,
                    'status' => $m['status'] ?? null,
                    'last_scanned_at' => $m['last_scanned_at'] ?? null,
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

                $epc = trim($map['epc_code'] ?? '');
                if ($epc === '') {
                    $results[] = [
                        'epc_code' => null,
                        'success' => false,
                        'message' => 'Missing epc_code',
                    ];

                    continue;
                }

                // Skip duplicates within same request
                if (isset($seenEpcs[$epc])) {
                    $results[] = [
                        'epc_code' => $epc,
                        'success' => false,
                        'message' => 'Duplicate epc in request - skipped',
                    ];

                    continue;
                }
                $seenEpcs[$epc] = true;

                // Resolve product
                if (empty($map['product_id'])) {
                    $results[] = [
                        'epc_code' => $epc,
                        'success' => false,
                        'message' => 'Missing product_id',
                    ];

                    continue;
                }

                $product = Product::find($map['product_id']);
                if (! $product) {
                    $results[] = [
                        'epc_code' => $epc,
                        'success' => false,
                        'message' => 'Mapping failed! Product not found.',
                    ];

                    continue;
                }

                $resolvedLocationId = $product->location_id;

                // Check existing tag
                $tag = Inventory::where('epc_code', $epc)->first();
                $isNewTag = false;
                $previousProductId = null;

                // Duplicate-safe check → DO NOT perform activity
                if ($tag && intval($tag->product_id) === intval($product->id)) {
                    $results[] = [
                        'epc_code' => $epc,
                        'tag_id' => $tag->id,
                        'product_id' => $product->id,
                        'location_id' => $tag->location_id,
                        'success' => false,
                        'message' => 'Duplicate tag — already mapped to this product (skipped)',
                        'movement' => null,
                    ];

                    continue;
                }

                // CREATE NEW TAG
                if (! $tag) {
                    $tag = Inventory::create([
                        'epc_code' => $epc,
                        'tag_code' => $map['tag_code'] ?? null,
                        'product_id' => $product->id,
                        'location_id' => $resolvedLocationId,
                        'trolley_id' => $map['trolley_id'] ?? null,
                        'status' => 1,
                        'mapped_at' => Carbon::now(),
                        'last_scanned_at' => Carbon::now(),
                    ]);
                    $isNewTag = true;
                }
                // REASSIGN TAG
                else {
                    $previousProductId = $tag->product_id;

                    $tag->update([
                        'product_id' => $product->id,
                        'tag_code' => $map['tag_code'] ?? $tag->tag_code,
                        'location_id' => $resolvedLocationId,
                        'trolley_id' => $map['trolley_id'] ?? $tag->trolley_id,
                        'status' => $tag->status,
                        'last_scanned_at' => Carbon::now(),
                    ]);
                }

                // MOVEMENT
                $movementInfo = [];

                if ($isNewTag) {
                    $activity = InventoryActivity::create([
                        'product_id' => $product->id,
                        'inventory_id' => $tag->id,
                        'adjust_qty' => 1,
                        'inward' => 1,
                        'outward' => 0,
                        'trans_type' => 'tag_mapping',
                        'remarks' => 'Tag created & assigned',
                        'status' => 1,
                        'location_id' => $resolvedLocationId,
                        'created_by' => $userId,
                        'updated_by' => $userId,
                    ]);
                    $movementInfo['assign'] = $activity->toArray();

                } elseif ($previousProductId !== $product->id) {

                    // OUT from previous
                    $outAct = InventoryActivity::create([
                        'product_id' => $previousProductId,
                        'inventory_id' => $tag->id,
                        'adjust_qty' => 1,
                        'inward' => 0,
                        'outward' => 1,
                        'trans_type' => 'tag_reassign_out',
                        'remarks' => 'Tag reassigned - removed from previous product',
                        'status' => 1,
                        'location_id' => $resolvedLocationId,
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
                        'remarks' => 'Tag reassigned - assigned to new product',
                        'status' => 1,
                        'location_id' => $resolvedLocationId,
                        'created_by' => $userId,
                        'updated_by' => $userId,
                    ]);
                    $movementInfo['reassign_in'] = $inAct->toArray();
                }

                $results[] = [
                    'epc_code' => $epc,
                    'tag_id' => $tag->id,
                    'product_id' => $product->id,
                    'location_id' => $resolvedLocationId,
                    'success' => true,
                    'message' => $isNewTag ? 'Tag created and mapped' : 'Tag mapped/updated',
                    'movement' => $movementInfo,
                ];
            }

            DB::commit();

            // -----------------------------
            // DETERMINE OVERALL SUCCESS
            // -----------------------------
            $hasSuccess = false;
            foreach ($results as $r) {
                if (! empty($r['success']) && $r['success'] === true) {
                    $hasSuccess = true;
                    break;
                }
            }

            return response()->json([
                'success' => $hasSuccess,
                'message' => $hasSuccess ? 'Mapping completed' : 'All tags were duplicates or failed',
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
            'update_tag_status' => 'nullable|string',
            'status' => 'nullable|integer', // allow explicit status override
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
            $adjustQty = $request->filled('adjust_qty') ? intval($request->adjust_qty) : ($inward > 0 ? $inward : ($outward > 0 ? $outward : 0));
            $type = $request->trans_type;
            $remarks = $request->remarks ?? null;
            $locationId = $request->location_id ?? null;
            $status = $request->has('status') ? intval($request->status) : 1;

            // Create InventoryActivity - opening/closing set by model boot()
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

            // Update tag status / location if inventory_id provided
            if ($inventoryId) {
                $tag = Inventory::find($inventoryId);
                if ($tag) {
                    $updateTag = [];
                    if ($request->filled('update_tag_status')) {
                        $updateTag['status'] = $request->get('update_tag_status');
                    }
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

            return response()->json(['success' => false, 'message' => 'Failed to record movement: '.$e->getMessage()], 500);
        }
    }

    /**
     * Fetch full details of an EPC tag: inventory record, mapped product,
     * and last 10 inventory activities.
     */
    // public function tagDetailsByEpc($epc)
    // {
    //     try {
    //         $epc = trim($epc);

    //         // Load tag + product relation
    //         $tag = Inventory::with('product')
    //             ->where('epc_code', $epc)
    //             ->first();

    //         if (! $tag) {
    //             return response()->json([
    //                 'success' => false,
    //                 'message' => 'Tag not found',
    //                 'inventory' => null,
    //                 'product' => null,
    //                 'history' => [],
    //             ], 200);
    //         }

    //         // Get history of last 10 activities
    //         $history = InventoryActivity::where('inventory_id', $tag->id)
    //             ->orderBy('created_at', 'desc')
    //             ->limit(10)
    //             ->get();

    //         return response()->json([
    //             'success' => true,
    //             'message' => 'Tag details loaded',
    //             'inventory' => $tag,
    //             'product' => $tag->product,
    //             'history' => $history,
    //         ], 200);

    //     } catch (\Throwable $e) {
    //         Log::error('tagDetailsByEpc error: '.$e->getMessage(), ['exception' => $e]);

    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Server error: '.$e->getMessage(),
    //         ], 500);
    //     }
    // }

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
}
