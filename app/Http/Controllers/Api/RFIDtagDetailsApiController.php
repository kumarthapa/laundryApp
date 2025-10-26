<?php

namespace App\Http\Controllers\Api;

use App\Helpers\LocaleHelper;
use App\Http\Controllers\Controller;
use App\Models\products\ProductProcessHistory;
use App\Models\products\Products;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class RFIDtagDetailsApiController extends Controller
{
    protected $products;

    // Stage dependencies: which stages must be PASSED before moving to target stage
    protected $stageDependencies = [
        'packaging' => ['tape_edge_qc', 'zip_cover_qc'],
        // you can add more rules later
        // 'dispatch' => ['packaging'],
    ];

    public function __construct()
    {
        $this->products = new Products;
    }

    /**
     * Fetch product details by RFID tag ID.
     */
    public function getProductDetailsByTagId(Request $request)
    {
        Log::info('getProductDetailsByTagId: '.json_encode($request->all()));

        try {
            // Validation
            $validator = Validator::make($request->all(), [
                'tag_id' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation errors',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $tagId = $request->input('tag_id');
            Log::info('tagId: '.json_encode($tagId));

            // Scope lookup to login user's location if available
            $loginLocationId = LocaleHelper::getLoginUserLocationId();

            $productQuery = $this->products->with('processHistory')->where('rfid_tag', $tagId);

            if ($loginLocationId) {
                // If products table has location_id, we restrict to it
                $productQuery->where('location_id', $loginLocationId);
            }

            $product = $productQuery->first();

            Log::info('product found: '.($product ? 'yes' : 'no'));

            if (! $product) {
                return response()->json([
                    'success' => false,
                    'message' => 'Product not found',
                ], 404);
            }

            // Get latest process history
            $latestHistory = $product->processHistory()->orderBy('changed_at', 'desc')->first();

            // Decode defects_points JSON
            $latestDefectsPoints = ! empty($latestHistory->defects_points)
                ? json_decode($latestHistory->defects_points, true)
                : [];

            // Format product data
            $formattedProduct = [
                'id' => $product->id,
                'product_name' => $product->product_name,
                'sku' => $product->sku,
                'size' => $product->size,
                'quantity' => $product->quantity,
                'latest_stage' => $latestHistory->stages ?? null,
                'latest_status' => $latestHistory->status ?? null,
                'latest_remarks' => $latestHistory->remarks ?? null,
                'latest_defects_points' => $latestDefectsPoints,
                'created_at' => $product->created_at ? $product->created_at->toDateTimeString() : null,
                'tag_id' => $product->rfid_tag,
                'qa_code' => $product->qa_code,
                'location_id' => $product->location_id ?? null,
            ];
            Log::info('getProductDetailsByTagId formattedProduct: '.json_encode($formattedProduct));

            return response()->json([
                'success' => true,
                'message' => 'Product fetched successfully',
                'product' => $formattedProduct,
            ]);

        } catch (Exception $e) {
            Log::error('Error fetching product details: '.$e->getMessage(), ['trace' => $e->getTraceAsString()]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch product details',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update product stage and QC status by RFID tag ID
     */
    public function updateProductStage(Request $request)
    {
        Log::info('updateProductStage: '.json_encode($request->all()));

        try {
            // Validation
            $validator = Validator::make($request->all(), [
                'tag_id' => 'required|string',
                'stage' => 'required|string',  // e.g., bonding_qc, tape_edge_qc, zip_cover_qc, packaging
                'status' => 'nullable|string|in:PASS,FAIL,PENDING,REWORK',
                'remarks' => 'nullable|string',
                'defects_points' => 'nullable|array',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation errors',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $tagId = $request->input('tag_id');
            $stage = $request->input('stage');
            $qcStatus = $request->input('status', 'PENDING');
            $remarks = $request->input('remarks');
            $defectsPoints = $request->input('defects_points', []); // default empty array

            // Scope lookup to login user's location if available
            $loginLocationId = LocaleHelper::getLoginUserLocationId();

            $productQuery = $this->products->where('rfid_tag', $tagId);
            if ($loginLocationId) {
                $productQuery->where('location_id', $loginLocationId);
            }

            $product = $productQuery->first();

            if (! $product) {
                return response()->json([
                    'success' => false,
                    'message' => 'Product not found or not accessible from your location',
                ], 404);
            }

            // 🚫 Prevent skipping mandatory stages
            if (array_key_exists($stage, $this->stageDependencies)) {
                $requiredStages = $this->stageDependencies[$stage];

                // Ensure ALL required stages exist with PASS
                $passedCount = ProductProcessHistory::where('product_id', $product->id)
                    ->whereIn('stages', $requiredStages)
                    ->where('status', 'PASS')
                    ->distinct('stages')
                    ->count('stages');

                if ($passedCount < count($requiredStages)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Product cannot move directly to '.$stage.'. Required upstream QC stages not passed.',
                    ], 422);
                }
            }

            // 🔍 Check if this stage is already PASS in history
            $existingPass = ProductProcessHistory::where('product_id', $product->id)
                ->where('stages', $stage)
                ->where('status', 'PASS')
                ->first();

            if ($existingPass) {
                // Already passed — don't allow duplicate pass updates
                return response()->json([
                    'success' => false,
                    'message' => 'This stage is already PASS',
                ], 409);
            } else {
                // bonding qc must be done first for other stages
                if ($stage !== 'bonding_qc' && $product->processHistory()->where('stages', 'bonding_qc')->where('status', 'PASS')->doesntExist()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Bonding QC is not done yet',
                    ], 422);
                }
            }

            // Update product QC meta (who updated & when)
            $product->qc_status_updated_by = auth()->id() ?? null;
            $product->qc_confirmed_at = now();
            $product->save();

            // Log process history with defects points
            $product->processHistory()->create([
                'stages' => $stage,
                'status' => $qcStatus,
                'defects_points' => ! empty($defectsPoints) ? json_encode($defectsPoints) : null,
                'remarks' => $remarks,
                'changed_by' => auth()->id() ?? null,
                'changed_at' => now(),
                'location_id' => $loginLocationId,
            ]);

            // Final stage auto lock tag
            if ($stage === 'packaging') {
                if ($product->bondingPlanProduct) {
                    try {
                        $product->bondingPlanProduct->update([
                            'is_locked' => 1,
                            'locked_by' => auth()->id() ?? null,
                        ]);
                    } catch (Exception $e) {
                        Log::warning('Failed to update bondingPlanProduct lock: '.$e->getMessage(), [
                            'product_id' => $product->id,
                        ]);
                    }
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Product stage updated successfully',
                'product' => [
                    'id' => $product->id,
                    'product_name' => $product->product_name,
                    'sku' => $product->sku,
                    'size' => $product->size,
                    'tag_id' => $product->rfid_tag,
                    'qa_code' => $product->qa_code,
                    'quantity' => $product->quantity,
                    'status' => $qcStatus,
                    'stage' => $stage,
                    'created_at' => $product->created_at ? $product->created_at->toDateTimeString() : null,
                ],
            ]);
        } catch (Exception $e) {
            Log::error('Error updating product stage: '.$e->getMessage(), ['trace' => $e->getTraceAsString()]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update product stage',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update basic product details (name / sku) by tag id
     */
    public function updateProductDetails(Request $request)
    {
        Log::info('updateProductDetails request: '.json_encode($request->all()));

        try {
            // Validation: only require tag_id and product_name for this endpoint
            $validator = Validator::make($request->all(), [
                'tag_id' => 'required|string',
                'product_name' => 'nullable|string|max:255',
                'sku' => 'nullable|string|max:100',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation errors',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $tagId = trim($request->input('tag_id'));
            $productName = trim($request->input('product_name') ?? '');
            $sku = trim($request->input('sku') ?? '');

            // basic sanitization (strip HTML tags)
            $productName = strip_tags($productName);
            $sku = strip_tags($sku);

            // Scope lookup to login user's location if available
            $loginLocationId = LocaleHelper::getLoginUserLocationId();

            $productQuery = $this->products->where('rfid_tag', $tagId);
            if ($loginLocationId) {
                $productQuery->where('location_id', $loginLocationId);
            }

            $product = $productQuery->first();

            if (! $product) {
                Log::warning("updateProductDetails: product not found for tag_id={$tagId}");

                return response()->json([
                    'success' => false,
                    'message' => 'Product not found or not accessible from your location',
                ], 404);
            }

            // If name or sku provided and different, update
            $needsUpdate = false;
            $updateData = [];

            if ($productName && $productName !== $product->product_name) {
                $updateData['product_name'] = $productName;
                $needsUpdate = true;
            }

            if ($sku && $sku !== $product->sku) {
                $updateData['sku'] = $sku;
                $needsUpdate = true;
            }

            if ($needsUpdate) {
                $updateData['qc_status_updated_by'] = auth()->id() ?? null;
                $product->update($updateData);

                Log::info("updateProductDetails: updated product_id={$product->id}", [
                    'updated_fields' => $updateData,
                    'by' => auth()->id() ?? null,
                ]);
            } else {
                Log::info("updateProductDetails: no changes for product_id={$product->id}");
            }

            // Return trimmed product payload
            return response()->json([
                'success' => true,
                'message' => 'Product details updated successfully',
                'product' => [
                    'id' => $product->id,
                    'product_name' => $product->product_name,
                    'sku' => $product->sku,
                    'size' => $product->size,
                    'tag_id' => $product->rfid_tag,
                    'qa_code' => $product->qa_code,
                    'quantity' => $product->quantity,
                    'location_id' => $product->location_id ?? null,
                    'created_at' => $product->created_at ? $product->created_at->toDateTimeString() : null,
                    'updated_at' => $product->updated_at ? $product->updated_at->toDateTimeString() : null,
                ],
            ], 200);

        } catch (Exception $e) {
            Log::error('Error in updateProductDetails: '.$e->getMessage(), ['trace' => $e->getTraceAsString()]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update product details',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
