<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\products\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ProductsApiController extends Controller
{
    protected $product;

    public function __construct()
    {
        $this->product = new Product;
    }

    /**
     * Get paginated list of products with optional filters.
     */
    public function getProducts(Request $request)
    {
        Log::info('getProducts: '.json_encode($request->all()));

        try {
            // ----------------------
            // VALIDATION
            // ----------------------
            $validator = Validator::make($request->all(), [
                'search' => 'nullable|string|max:255',
                'status' => 'nullable|string|in:active,inactive,all',
                'rfid_code' => 'nullable|string',
                'product_code' => 'nullable|string',
                'start_date' => 'nullable|date',
                'end_date' => 'nullable|date|after_or_equal:start_date',
                'page' => 'nullable|integer|min:1',
                'limit' => 'nullable|integer|min:1|max:500',
                'location_id' => 'nullable|integer',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation errors',
                    'errors' => $validator->errors(),
                ], 422);
            }

            // ------------------------------------------------
            // READ PARAMETERS
            // ------------------------------------------------
            $search = $request->input('search', '');
            $epcFilter = $request->input('rfid_code', '');
            $prodCode = $request->input('product_code', '');
            $status = $request->input('status', '');
            $startDate = $request->input('start_date', null);
            $endDate = $request->input('end_date', null);
            $page = max(1, (int) $request->input('page', 1));
            $limit = (int) $request->input('limit', 20);
            $locationId = $request->input('location_id', null);

            // ------------------------------------------------
            // SUBQUERY FOR LAST_ACTIVITY
            // ------------------------------------------------
            $subMax = DB::table('inventory_activity')
                ->select('product_id', DB::raw('MAX(trans_id) as max_trans_id'))
                ->groupBy('product_id');

            $lastActivity = DB::table('inventory_activity as ia')
                ->joinSub($subMax, 'm', function ($join) {
                    $join->on('ia.product_id', '=', 'm.product_id')
                        ->on('ia.trans_id', '=', 'm.max_trans_id');
                })
                ->select('ia.*');

            // ------------------------------------------------
            // MAIN QUERY WITH ANY_VALUE() FIXES
            // ------------------------------------------------
            $query = DB::table('products as p')
                ->leftJoin('rfid_tags as t', 't.product_id', '=', 'p.id')
                ->leftJoinSub($lastActivity, 'last_ia', function ($join) {
                    $join->on('p.id', '=', 'last_ia.product_id');
                })
                ->select(
                    'p.id',

                    DB::raw('ANY_VALUE(p.product_name) as product_name'),
                    DB::raw('ANY_VALUE(p.sku) as product_code'),
                    DB::raw('ANY_VALUE(p.category) as category'),
                    DB::raw('ANY_VALUE(p.price) as price'),
                    DB::raw('ANY_VALUE(p.expected_life_cycles) as expected_life_cycles'),
                    DB::raw('ANY_VALUE(p.status) as product_status'),

                    DB::raw('COUNT(t.id) as quantity'),

                    DB::raw('ANY_VALUE(last_ia.trans_type) as last_trans_type'),
                    DB::raw('ANY_VALUE(last_ia.inward) as last_inward'),
                    DB::raw('ANY_VALUE(last_ia.outward) as last_outward'),
                    DB::raw('ANY_VALUE(last_ia.opening_stock) as last_opening_stock'),
                    DB::raw('ANY_VALUE(last_ia.closing_stock) as last_closing_stock'),
                    DB::raw('ANY_VALUE(last_ia.created_at) as last_activity_at')
                )
                ->groupBy('p.id');

            // ------------------------------------------------
            // OPTIONAL LOCATION FILTERS
            // ------------------------------------------------
            if (class_exists(\App\Helpers\LocaleHelper::class)) {
                try {
                    $query = \App\Helpers\LocaleHelper::commonWhereLocationCheck($query, 'p');
                } catch (\Throwable $t) {
                }
            }

            // ------------------------------------------------
            // FILTERS
            // ------------------------------------------------
            if (! empty($search)) {
                $query->where(function ($q) use ($search) {
                    $q->where('p.product_name', 'like', "%{$search}%")
                        ->orWhere('p.sku', 'like', "%{$search}%")
                        ->orWhere('p.category', 'like', "%{$search}%");
                });
            }

            if (! empty($prodCode)) {
                $query->where('p.sku', $prodCode);
            }

            if (! empty($epcFilter)) {
                $query->where('t.epc_code', $epcFilter);
            }

            if (! empty($status) && $status !== 'all') {
                $query->where('p.status', $status === 'active' ? 1 : 0);
            }

            if (! empty($locationId)) {
                $query->where(function ($q) use ($locationId) {
                    $q->where('t.location_id', $locationId)
                        ->orWhereNull('t.location_id');
                });
            }

            if ($startDate && $endDate) {
                $query->whereBetween('p.created_at', [$startDate, $endDate]);
            }

            // ------------------------------------------------
            // COUNT DISTINCT PRODUCTS
            // ------------------------------------------------
            $total = (clone $query)->get()->count();

            // ------------------------------------------------
            // PAGINATION + SORTING
            // ------------------------------------------------
            $allowedSorts = [
                'p.created_at', 'product_name', 'product_code', 'quantity',
                'last_activity_at', 'category', 'price',
            ];

            $sort = $request->get('sort', 'p.created_at');
            if (! in_array($sort, $allowedSorts)) {
                $sort = 'p.created_at';
            }

            $order = strtolower($request->get('order', 'desc')) === 'asc' ? 'asc' : 'desc';

            $rows = $query
                ->orderBy($sort, $order)
                ->offset(($page - 1) * $limit)
                ->limit($limit)
                ->get();

            // ------------------------------------------------
            // FORMAT OUTPUT
            // ------------------------------------------------
            $formatted = $rows->map(function ($row) {
                return [
                    'id' => $row->id,
                    'product_name' => $row->product_name,
                    'product_code' => $row->product_code,
                    'category' => $row->category,
                    'price' => (float) ($row->price ?? 0),
                    'expected_life_cycles' => $row->expected_life_cycles,
                    'quantity' => (int) ($row->quantity ?? 0),

                    'last_activity' => [
                        'trans_type' => $row->last_trans_type,
                        'inward' => (int) ($row->last_inward ?? 0),
                        'outward' => (int) ($row->last_outward ?? 0),
                        'opening_stock' => (int) ($row->last_opening_stock ?? 0),
                        'closing_stock' => (int) ($row->last_closing_stock ?? 0),
                        'at' => $row->last_activity_at,
                    ],
                ];
            });
            Log::info('Products fetched response '.json_encode($formatted));

            return response()->json([
                'success' => true,
                'message' => 'Products fetched successfully',
                'products' => $formatted,
                'pagination' => [
                    'total' => $total,
                    'per_page' => $limit,
                    'current_page' => $page,
                    'last_page' => ceil($total / $limit),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching products: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch products',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
