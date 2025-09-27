<?php

namespace App\Http\Controllers\Api;

use App\Helpers\UtilityHelper;
use App\Http\Controllers\Controller;
use App\Models\products\BondingPlanProduct;
use App\Models\products\ProductProcessHistory;
use App\Models\products\Products;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

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
        Log::info('getPlanProducts: '.json_encode($request->all()));

        try {
            // Validate incoming request
            $validator = Validator::make($request->all(), [
                'search' => 'nullable|string|max:255',
                'start_date' => 'nullable|date',
                'end_date' => 'nullable|date|after_or_equal:start_date',
                'page' => 'nullable|integer|min:1',
                'limit' => 'nullable|integer|min:1|max:500',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation errors',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $search = $request->input('search', '');
            $startDate = $request->input('start_date', null);
            $endDate = $request->input('end_date', null);
            $page = $request->input('page', 1);
            $limit = $request->input('limit', 500);

            // Use BondingPlanProduct model query
            $query = BondingPlanProduct::query();

            // Apply search filters on BondingPlanProduct fields
            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('product_name', 'LIKE', "%$search%")
                        ->orWhere('sku', 'LIKE', "%$search%")
                        ->orWhere('qa_code', 'LIKE', "%$search%")
                        ->orWhere('model', 'LIKE', "%$search%")
                        ->orWhere('size', 'LIKE', "%$search%");
                });
            }

            // Apply date range filter on created_at (bonding plan created date)
            if ($startDate && $endDate) {
                $query->whereBetween('created_at', [$startDate, $endDate]);
            }
            // Only Non Writed Model will  Write
            // $query->where('is_write', 0);

            // Pagination
            $pln_products = $query->orderBy('created_at', 'desc')
                ->paginate($limit, ['*'], 'page', $page);

            // Format response data including related latest product info
            $formattedProducts = $pln_products->map(function ($bondingProduct) {
                // Get one related product to get RFID tag if exists (or adjust as per logic)
                $relatedProduct = $bondingProduct->products()->orderBy('created_at', 'desc')->first();

                return [
                    'id' => $bondingProduct->id,
                    'product_name' => $bondingProduct->product_name,
                    'model' => $bondingProduct->model,
                    'qa_code' => $bondingProduct->qa_code,
                    'sku' => $bondingProduct->sku,
                    'size' => $bondingProduct->size,
                    'is_write' => $bondingProduct->is_write,
                    'write_by' => $bondingProduct->write_by,
                    'write_date' => $bondingProduct->write_date,
                    'rfid_tag' => $relatedProduct ? $relatedProduct->rfid_tag : null,
                    'quantity' => $bondingProduct->quantity,
                    'created_at' => $bondingProduct->created_at->toDateTimeString(),
                ];
            });
            Log::info('formattedProducts '.json_encode($formattedProducts));

            return response()->json([
                'success' => true,
                'message' => 'Products fetched successfully',
                'data' => [
                    'products' => $formattedProducts,
                    'pagination' => [
                        'total' => $pln_products->total(),
                        'per_page' => $pln_products->perPage(),
                        'current_page' => $pln_products->currentPage(),
                        'last_page' => $pln_products->lastPage(),
                        'from' => $pln_products->firstItem(),
                        'to' => $pln_products->lastItem(),
                    ],
                ],
            ]);
        } catch (Exception $e) {
            Log::error('Error fetching products: '.$e->getMessage(), ['trace' => $e->getTraceAsString()]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch products',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get paginated list of products with optional filters.
     * Example filters: search term, status, date range
     */
    public function getProducts(Request $request)
    {
        Log::info('getProducts: '.json_encode($request->all()));
        try {
            // Validate incoming request
            $validator = Validator::make($request->all(), [
                'search' => 'nullable|string|max:255',
                'status' => 'nullable|string|in:PASS,FAILED,PENDING,all',
                'qa_code' => 'nullable|string',
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
            $qa_code = $request->input('qa_code', '');
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
                        ->orWhere('qa_code', 'LIKE', "%$search%")
                        ->orWhere('size', 'LIKE', "%$search%")
                        ->orWhere('sku', 'LIKE', "%$search%");
                });
            }

            // Apply status filter if specified and not 'all'
            // if ($status && strtolower($status) !== 'all') {
            //     $query->where('qc_status', $status);
            // }

            // Apply date range filter
            if ($startDate && $endDate) {
                $query->whereBetween('created_at', [$startDate, $endDate]);
            }

            // Pagination
            $products = $query->orderBy('created_at', 'desc')
                ->paginate($limit, ['*'], 'page', $page);

            Log::info('Total count before paginate: '.$query->count());

            // --- after $products = $query->orderBy(...)->paginate($limit, ['*'], 'page', $page);

            // Format each product into simple array
            $formattedCollection = $products->getCollection()->map(function ($product) {
                $latestHistory = $product->processHistory()->orderBy('changed_at', 'desc')->first();

                return [
                    'id' => $product->id,
                    'product_name' => $product->product_name,
                    'sku' => $product->sku,
                    'size' => $product->size,
                    'tag_id' => $product->rfid_tag,
                    'qa_code' => $product->qa_code,
                    'quantity' => $product->quantity,
                    'status' => isset($latestHistory) ? $latestHistory->status : 'PENDING',
                    'stage' => isset($latestHistory) ? ($latestHistory->stages ?? $latestHistory->stage ?? 'BONDING') : 'BONDING',
                    'created_at' => $product->created_at->toDateTimeString(),
                ];
            });

            // Put formatted collection back into paginator so pagination helpers remain correct
            $products->setCollection($formattedCollection);

            // Build response payload using paginator metadata
            return response()->json([
                'success' => true,
                'message' => 'Products fetched successfully',
                'products' => $products->items(), // array of formatted items
                'pagination' => [
                    'total' => $products->total(),
                    'per_page' => $products->perPage(),
                    'current_page' => $products->currentPage(),
                    'last_page' => $products->lastPage(),
                    'from' => $products->firstItem(),
                    'to' => $products->lastItem(),
                ],
            ]);

        } catch (Exception $e) {
            Log::error('Error fetching products: '.$e->getMessage(), ['trace' => $e->getTraceAsString()]);

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
        Log::info('getProductStages: '.json_encode($request->all()));
        try {
            $stages = $this->products->getProductStages();

            return response()->json([
                'success' => true,
                'message' => 'Product stages fetched successfully',
                'data' => $stages,
            ]);
        } catch (Exception $e) {
            Log::error('Error fetching product stages: '.$e->getMessage(), ['trace' => $e->getTraceAsString()]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch product stages',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function getStagesAndStatus(Request $request)
    {
        Log::info('getStagesAndStatus: '.json_encode($request->all()));

        try {
            $currentStage = $request->get('current_stage'); // Android sends
            $currentStatus = $request->get('current_status'); // Android sends QC status if any

            $data = UtilityHelper::getProductStagesAndStatus($currentStage, $currentStatus);
            Log::info('getProductStagesAndStatus response : '.json_encode($data));

            return response()->json([
                'success' => true,
                'message' => 'Product stages and status fetched successfully',
                'data' => $data,
            ]);
        } catch (Exception $e) {
            Log::error('Error fetching product stages and status: '.$e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch product stages and status',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update QA Code for a bonding plan product
     */
    public function updateQaCode(Request $request)
    {
        $user = Auth::user();
        Log::info('updateQaCode: '.json_encode($request->all()));

        try {
            // Validate input
            $validator = Validator::make($request->all(), [
                'product_id' => 'required|integer|exists:bonding_plan_products,id',
                'qa_code' => 'required|string|max:255|unique:bonding_plan_products,qa_code',
                'rfid_tag' => 'required|string|max:255|unique:products,rfid_tag',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation errors',
                    'errors' => $validator->errors(),
                ], 422);
            }

            // Get input values
            $productId = $request->input('product_id');
            $qaCode = $request->input('qa_code');

            // Check if QA code already exists for another product
            $existingQa = BondingPlanProduct::where('qa_code', $qaCode)
                ->where('id', '<>', $productId) // exclude current product
                ->first();

            if ($existingQa) {
                return response()->json([
                    'success' => false,
                    'message' => 'The QA code has already been taken.',
                ], 422);
            }

            // Find the bonding product
            $bondingProduct = BondingPlanProduct::findOrFail($productId);
            $bondingProduct->qa_code = $qaCode;
            $bondingProduct->quantity = 1;
            $bondingProduct->is_write = 1;
            $bondingProduct->write_by = $user->id ?? 0;
            $bondingProduct->write_date = now();
            $bondingProduct->save();

            // Insert into products table
            $productsData = [
                'bonding_plan_product_id' => $bondingProduct->id,
                'product_name' => $bondingProduct->product_name,
                'qa_code' => $bondingProduct->qa_code,
                'rfid_tag' => $request->input('rfid_tag'),
                'sku' => $bondingProduct->sku ?? null,
                'size' => $bondingProduct->size,
                'quantity' => $bondingProduct->quantity ?? 0,
                'reference_code' => $bondingProduct->reference_code ?? null,
            ];

            $product = Products::create($productsData);

            // Insert into product_process_history table
            $historyData = [
                'product_id' => $product->id,  // <- use products.id, NOT bonding_plan_products.id
                'stages' => 'bonding_qc',      // corrected column name
                'status' => 'PENDING',
                'defects_points' => null,      // corrected column name
            ];

            ProductProcessHistory::create($historyData);

            return response()->json([
                'success' => true,
                'message' => 'QA Code updated successfully',
                'data' => [
                    'id' => $bondingProduct->id,
                    'product_name' => $bondingProduct->product_name,
                    'qa_code' => $bondingProduct->qa_code,
                    'rfid_tag' => $request->input('rfid_tag'),
                ],
            ]);

        } catch (Exception $e) {
            Log::error('Error updating QA code: '.$e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update QA code',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
