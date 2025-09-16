<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\products\Products;
use Illuminate\Http\Request;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Helpers\UtilityHelper;

class ProductsApiController extends Controller
{
    protected $products;

    public function __construct()
    {
        $this->products = new Products;
    }

    /**
     * Get paginated list of products with optional filters.
     * Example filters: search term, status, date range
     */
    public function getPlanProducts(Request $request)
    {
        Log::info("getPlanProducts: ".json_encode($request->all()));
        try {
            // Validate incoming request
            $validator = Validator::make($request->all(), [
                'search' => 'nullable|string|max:255',
                'status' => 'nullable|string|in:PASS,FAILED,PENDING,all',
                'start_date' => 'nullable|date',
                'end_date' => 'nullable|date|after_or_equal:start_date',
                'page' => 'nullable|integer|min:1',
                'limit' => 'nullable|integer|min:1|max:100',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation errors',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $search = $request->input('search', '');
            $status = $request->input('status', '');
            $startDate = $request->input('start_date', null);
            $endDate = $request->input('end_date', null);
            $page = $request->input('page', 1);
            $limit = $request->input('limit', 20);

            $query = $this->products->newQuery();

            // Apply search filter
            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('product_name', 'LIKE', "%$search%")
                        ->orWhere('sku', 'LIKE', "%$search%");
                });
            }

            // Apply status filter if specified and not 'all'
            if ($status && strtolower($status) !== 'all') {
                $query->where('qc_status', $status);
            }

            // Apply date range filter
            if ($startDate && $endDate) {
                $query->whereBetween('created_at', [$startDate, $endDate]);
            }

            // Pagination
            $products = $query->orderBy('created_at', 'desc')
                ->paginate($limit, ['*'], 'page', $page);

            // Format response data
            $formattedProducts = $products->map(function ($product) {
                $latestHistory = $product->processHistory()->orderBy('changed_at', 'desc')->first();
                return [
                    'id' => $product->id,
                    'product_name' => $product->product_name,
                    'sku' => $product->sku,
                    'size' => $product->size,
                    'tag_id' => $product->rfid_tag,
                    'quantity' => $product->quantity,
                    'qc_status' => isset($latestHistory) ? $latestHistory->status : $product->qc_status,
                    'current_stage' => isset($latestHistory) ? $latestHistory->stage : $product->current_stage,
                    'created_at' => $product->created_at->toDateTimeString(),
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Products fetched successfully',
                'data' => [
                    'products' => $formattedProducts,
                    'pagination' => [
                        'total' => $products->total(),
                        'per_page' => $products->perPage(),
                        'current_page' => $products->currentPage(),
                        'last_page' => $products->lastPage(),
                        'from' => $products->firstItem(),
                        'to' => $products->lastItem(),
                    ],
                ],
            ]);
        } catch (Exception $e) {
            Log::error("Error fetching products: " . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch products',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // Fetch product stages
    public function getProductStages(Request $request)
    {
        Log::info("getProductStages: ".json_encode($request->all()));
        try {
            $stages = $this->products->getProductStages();
            return response()->json([
                'success' => true,
                'message' => 'Product stages fetched successfully',
                'data' => $stages,
            ]);
        } catch (Exception $e) {
            Log::error("Error fetching product stages: " . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch product stages',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
        // Fetch product stages and status
    // public function getStagesAndStatus(Request $request)
    // {
    //     Log::info("getStagesAndStatus: ".json_encode($request->all()));
    //     try {
    //         $data = UtilityHelper::getProductStagesAndStatus();
    //         return response()->json([
    //             'success' => true,
    //             'message' => 'Product stages and status fetched successfully',
    //             'data' => $data,
    //         ]);
    //     } catch (Exception $e) {
    //         Log::error("Error fetching product stages and status: " . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Failed to fetch product stages and status',
    //             'error' => $e->getMessage(),
    //         ], 500);
    //     }
    // }
public function getStagesAndStatus(Request $request)
{
    Log::info("getStagesAndStatus: " . json_encode($request->all()));

    try {
        $currentStage = $request->get('current_stage'); // Android sends
        $currentStatus = $request->get('current_status'); // Android sends QC status if any

        $data = UtilityHelper::getProductStagesAndStatus($currentStage, $currentStatus);

        return response()->json([
            'success' => true,
            'message' => 'Product stages and status fetched successfully',
            'data' => $data,
        ]);
    } catch (Exception $e) {
        Log::error("Error fetching product stages and status: " . $e->getMessage(), [
            'trace' => $e->getTraceAsString()
        ]);
        return response()->json([
            'success' => false,
            'message' => 'Failed to fetch product stages and status',
            'error' => $e->getMessage(),
        ], 500);
    }
}

}
