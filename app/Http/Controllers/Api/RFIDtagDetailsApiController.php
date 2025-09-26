<?php

namespace App\Http\Controllers\Api;

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

            // Query product by RFID tag with history
            $product = $this->products->with('processHistory')->where('rfid_tag', $tagId)->first();

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
                'created_at' => $product->created_at->toDateTimeString(),
                'tag_id' => $product->rfid_tag,
                'qa_code' => $product->qa_code,
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
                'stage' => 'required|string',  // e.g., Bonding, Tapedge, Zip Cover, QC, Packing
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

            // Find product by RFID tag
            $product = $this->products->where('rfid_tag', $tagId)->first();

            if (! $product) {
                return response()->json([
                    'success' => false,
                    'message' => 'Product not found',
                ], 404);
            }

            // 🔍 Check if this stage is already PASS in history
            $existingPass = ProductProcessHistory::where('product_id', $product->id)
                ->where('stages', $stage)
                ->where('status', 'PASS')
                ->first();

            if ($existingPass && $qcStatus != 'FAIL') {
                return response()->json([
                    'success' => false,
                    'message' => "Stage '{$stage}' is already PASS",
                ], 409); // 409 = conflict
            }

            // Update product stage and QC status
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
            ]);

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
                    'created_at' => $product->created_at->toDateTimeString(),
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
}
