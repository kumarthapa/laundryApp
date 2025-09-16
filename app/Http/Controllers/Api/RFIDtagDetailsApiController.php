<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\products\Products;
use Illuminate\Http\Request;
use Exception;
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
    Log::info("getProductDetailsByTagId: " . json_encode($request->all()));

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

        // Query product by RFID tag
        $product = $this->products->with('processHistory')->where('rfid_tag', $tagId)->first();

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found',
            ], 404);
        }

        // Get latest process history
        $latestHistory = $product->processHistory()->orderBy('changed_at', 'desc')->first();

        // Format product data
        $formattedProduct = [
            'id' => $product->id,
            'product_name' => $product->product_name,
            'sku' => $product->sku,
            'size' => $product->size,
            'quantity' => $product->quantity,
            'current_stage' => $product->current_stage,
            'qc_status' => $product->qc_status,
            'latest_stage' => $latestHistory->stage ?? null,
            'latest_status' => $latestHistory->status ?? null,
            'latest_comments' => $latestHistory->comments ?? null,
            'created_at' => $product->created_at->toDateTimeString(),
            'tag_id' => $product->rfid_tag,
        ];

        return response()->json([
            'success' => true,
            'message' => 'Product fetched successfully',
            'product' => $formattedProduct,
        ]);
    } catch (Exception $e) {
        Log::error("Error fetching product details: " . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
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
    Log::info("updateProductStage: " . json_encode($request->all()));

    try {
        // Validation
        $validator = Validator::make($request->all(), [
            'tag_id' => 'required|string',
            'stage' => 'required|string',  // e.g., Bonding, Tapedge, Zip Cover, QC, Packing
            'qc_status' => 'nullable|string|in:PASS,FAILED,PENDING',
            'comments' => 'nullable|string',
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
        $qcStatus = $request->input('qc_status', 'PENDING');
        $comments = $request->input('comments');

        // Find product by RFID tag
        $product = $this->products->where('rfid_tag', $tagId)->first();

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found',
            ], 404);
        }

        // Update product stage and QC status
        $product->current_stage = $stage;
        $product->qc_status = $qcStatus;
        $product->qc_status_update_by = auth()->id() ?? null;
        $product->qc_confirmed_at = now();
        $product->save();

        // Log process history
        $product->processHistory()->create([
            'stage' => $stage,
            'status' => $qcStatus,
            'comments' => $comments,
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
                'quantity' => $product->quantity,
                'qc_status' => $product->qc_status,
                'current_stage' => $product->current_stage,
                'created_at' => $product->created_at->toDateTimeString(),
            ]
        ]);
    } catch (Exception $e) {
        Log::error("Error updating product stage: " . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
        return response()->json([
            'success' => false,
            'message' => 'Failed to update product stage',
            'error' => $e->getMessage(),
        ], 500);
    }
}
}