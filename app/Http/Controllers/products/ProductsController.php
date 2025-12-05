<?php

namespace App\Http\Controllers\products;

use App\Helpers\LocaleHelper;
use App\Helpers\TableHelper;
use App\Helpers\UtilityHelper;
use App\Http\Controllers\Controller;
use App\Models\location\Location;
use App\Models\products\Product;
use App\Models\user_management\Role;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;

class ProductsController extends Controller
{
    protected Product $product;

    public function __construct()
    {
        $this->product = new Product;
    }

    /**
     * Show products list page
     */
    public function index(Request $request)
    {
        $authUser = Auth::user();
        $role_info = Role::find($authUser->role_id);

        $headers = [
            ['created_at' => 'Created Date'],
            ['product_name' => 'Product Name'],
            ['sku' => 'SKU'],
            ['category' => 'Category'],
            ['expected_life_cycles' => 'Expected Life Cycles'],
            ['quantity' => 'Quantity'],
            ['status' => 'Status'],
        ];
        // Fetch locations for admins
        $locations_info = [];
        if ($role_info->role_type == 'super_role' || $role_info->role_type == 'admin_role') {
            $locations_info = Location::all();
        }

        // $productsOverview = LocaleHelper::getBondingProductSummaryCounts();

        // $productsOverview = [
        //     'total_model' => $productsOverview['total_model'] ?? 0,
        //     'total_qa_code' => $productsOverview['total_qa_code'] ?? 0,
        //     'total_writted' => $productsOverview['total_writted'] ?? 0,
        //     'total_pending' => $productsOverview['total_pending'] ?? 0,
        // ];

        $pageConfigs = ['pageHeader' => true, 'isFabButton' => true];
        $currentUrl = $request->url();
        $UtilityHelper = new UtilityHelper;
        $createPermissions = $UtilityHelper::CheckModulePermissions('products', 'create.products');
        $deletePermissions = $UtilityHelper::CheckModulePermissions('products', 'delete.products');
        // $table_headers = TableHelper::get_manage_table_headers($headers, true, true, true);
        // Readonly must be false so checkbox column is added
        $table_headers = TableHelper::get_manage_table_headers($headers, true, false, true, true, true);

        // $allProductsData = Product::with('processHistory')->get();
        // Log::info('Total products data 12: ', $allProductsData->toArray());

        return view('content.products.list')
            ->with('pageConfigs', $pageConfigs)
            ->with('table_headers', $table_headers)
            ->with('currentUrl', $currentUrl)
            // ->with('productsOverview', $productsOverview)
            ->with('createPermissions', $createPermissions)
            ->with('locations_info', $locations_info)
            ->with('deletePermissions', $deletePermissions);
    }

    /**
     * Build a single row for datatable from DB row/stdClass
     */
    protected function tableHeaderRowData($row, array $tagCounts = [])
    {
        $data = [];

        $createdAt = $row->created_at ? (string) $row->created_at : null;
        try {
            $createdAt = $createdAt ? Carbon::parse($createdAt)->format('d-m-Y H:i') : '';
        } catch (\Throwable $t) {
            $createdAt = $row->created_at;
        }

        $qty = $tagCounts[$row->id] ?? 0;

        $statusLabel = (isset($row->status) && intval($row->status) === 1) ? '<span class="badge rounded bg-label-success">Active</span>' : '<span class="badge rounded bg-label-secondary">Inactive</span>';

        $data['checkbox'] = '<div class="form-check"><input type="checkbox" class="row-checkbox form-check-input" data-id="'.e($row->id).'"></div>';
        $data['created_at'] = $createdAt;
        $data['product_name'] = $row->product_name ?? '';
        $data['sku'] = $row->sku ?? '';
        $data['category'] = $row->category ?? '';
        // $data['price'] = isset($row->price) ? number_format($row->price, 2) : '';
        $data['expected_life_cycles'] = $row->expected_life_cycles ?? '';
        $data['quantity'] = $qty;
        $data['status'] = $statusLabel;

        // Actions (view/edit/delete)
        $viewUrl = route('view.products', ['code' => $row->id]);
        $editUrl = route('edit.products', ['id' => $row->id]);
        $deleteUrl = route('delete.products', ['id' => $row->id]);

        $actions = '<div class="btn-group">';
        $actions .= '<a href="'.e($viewUrl).'" class="btn btn-sm btn-outline-primary">View</a>';
        $actions .= '<a href="'.e($editUrl).'" class="btn btn-sm btn-outline-secondary">Edit</a>';
        $actions .= '<a href="javascript:;" onclick="deleteRow(\''.e($deleteUrl).'\');" class="btn btn-sm btn-outline-danger">Delete</a>';
        $actions .= '</div>';

        $data['actions'] = $actions;

        return $data;
    }

    /**
     * AJAX: return table rows (datatables style)
     */
    public function list(Request $request)
    {
        $search = (string) $request->get('search', '');
        $limit = intval($request->get('length', 50));
        $offset = intval($request->get('start', 0));
        $sort = $request->get('sort', 'products.created_at');
        $order = $request->get('order', 'desc');

        // Filters: status and category and sku
        $filters = [
            'status' => $request->get('status', ''),
            'category' => $request->get('category', ''),
            'sku' => $request->get('sku', ''),
        ];

        // Date range (optional)
        $startDate = $request->get('start_date', null);
        $endDate = $request->get('end_date', null);
        if ($startDate && $endDate) {
            $filters['start_date'] = $startDate;
            $filters['end_date'] = $endDate;
        }

        // Get products using Product::search (returns collection of stdClass or arrays)
        $searchData = $this->product->search($search, $filters, $limit, $offset, $sort, $order);
        $totalRows = $this->product->get_found_rows($search, $filters);

        // Collect IDs to compute tag counts in one query (avoid N+1)
        $ids = array_map(fn ($r) => $r->id, $searchData->toArray() ?: []);
        $tagCounts = [];
        if (! empty($ids)) {
            $counts = DB::table('rfid_tags')
                ->select('product_id', DB::raw('COUNT(*) as cnt'))
                ->whereIn('product_id', $ids)
                ->groupBy('product_id')
                ->get()
                ->pluck('cnt', 'product_id')
                ->toArray();

            $tagCounts = $counts;
        }

        $dataRows = [];
        foreach ($searchData as $row) {
            $dataRows[] = $this->tableHeaderRowData($row, $tagCounts);
        }

        return response()->json([
            'data' => $dataRows,
            'recordsTotal' => $totalRows,
            'recordsFiltered' => $totalRows,
        ]);
    }

    /**
     * Create view
     */
    public function create(Request $request)
    {
        return view('content.products.create');
    }

    /**
     * Edit view
     */
    public function edit(Request $request, $id)
    {
        $product = Product::find($id);
        if (! $product) {
            return view('content.miscellaneous.no-data');
        }

        return view('content.products.edit', ['product' => $product]);
    }

    /**
     * Save (create/update) product
     */
    public function save(Request $request, $id = null)
    {
        $rules = [
            'product_name' => 'required|string|max:255',
            'sku' => 'required|string|max:255',
            'expected_life_cycles' => 'nullable|integer|min:0',
            'description' => 'nullable|string',
            'status' => 'nullable|in:0,1',
        ];

        // Unique sku rule
        if ($id) {
            $rules['sku'] .= '|unique:products,sku,'.$id;
        } else {
            $rules['sku'] .= '|unique:products,sku';
        }

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $request->only([
            'product_name',
            'sku',
            'category',
            'price',
            'expected_life_cycles',
            'description',
            'status',
        ]);

        // Normalize status to 0/1
        if (isset($data['status'])) {
            $data['status'] = intval($data['status']);
        } else {
            $data['status'] = 1;
        }

        DB::beginTransaction();
        try {
            if ($id) {
                $product = Product::findOrFail($id);
                $product->update($data);
                $action = 'update';
            } else {
                $product = Product::create($data);
                $action = 'create';
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Product successfully '.($action === 'create' ? 'created' : 'updated').'.',
                'product_id' => $product->id,
                'return_url' => route('products'),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Product save error: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'An error occurred: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete single or multiple products
     */
    public function delete(Request $request, $id = null)
    {
        $inputIds = $request->input('ids', null);
        $singleId = $id ?: $request->input('id', null);

        if (is_array($inputIds) && ! empty($inputIds)) {
            $ids = array_values(array_filter($inputIds, fn ($v) => ! is_null($v) && $v !== ''));
        } elseif ($singleId) {
            $ids = [$singleId];
        } else {
            return response()->json([
                'success' => false,
                'message' => 'No id(s) provided.',
            ], 422);
        }

        DB::beginTransaction();
        try {
            // Optional: prevent deletion if product has tags
            $productsWithTags = DB::table('rfid_tags')->whereIn('product_id', $ids)->pluck('product_id')->unique()->toArray();

            if (! empty($productsWithTags)) {
                // return error listing product codes which have tags
                $problemSkus = Product::whereIn('id', $productsWithTags)->pluck('sku')->toArray();

                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete product(s) which have mapped RFID tags. Unassign tags first.',
                    'products_with_tags' => $problemSkus,
                ], 400);
            }

            $deleted = Product::whereIn('id', $ids)->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "{$deleted} product(s) deleted successfully.",
                'deleted' => $deleted,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Product delete error: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Delete failed: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Download sample product import template
     */
    public function productImportFormat(Request $request)
    {
        try {
            $data = [];
            $header = [
                'sku', 'product_name', 'category', 'expected_life_cycles', 'description',
            ];
            $data[] = $header;
            $data[] = [];

            return Excel::download(new class($data) implements \Maatwebsite\Excel\Concerns\FromArray
            {
                protected $data;

                public function __construct(array $data)
                {
                    $this->data = $data;
                }

                public function array(): array
                {
                    return $this->data;
                }
            }, 'product_import_template.xlsx');
        } catch (\Exception $e) {
            Log::error('productImportFormat error: '.$e->getMessage());

            return back()->with('error', 'There was an error generating the template: '.$e->getMessage());
        }
    }

    /**
     * Bulk upload simple implementation (insert new or update existing)
     * Expected columns: sku, product_name, category, price, expected_life_cycles, description
     */
    public function bulkProductUpload(Request $request)
    {
        // print_r($request->all());
        // exit;
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:xlsx,xls,csv',
            'user_location_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors(), 'message' => 'Invalid upload parameters.']);
        }

        $file = $request->file('file');

        DB::beginTransaction();
        try {
            $collection = Excel::toCollection(null, $file)->first();
            if (! $collection || $collection->count() < 1) {
                return response()->json(['success' => false, 'message' => 'No rows found in the uploaded file.']);
            }

            // First row as header
            $header = $collection->first()->map(fn ($h) => strtolower(trim((string) $h)))->toArray();
            $rows = $collection->slice(1);

            // Map header indexes
            $indexMap = array_flip($header);

            $required = ['sku', 'product_name'];
            foreach ($required as $r) {
                if (! isset($indexMap[$r])) {
                    return response()->json(['success' => false, 'message' => "Missing required column: {$r}"]);
                }
            }

            $processed = 0;
            foreach ($rows as $row) {
                $row = $row->toArray();
                $productCode = $row[$indexMap['sku']] ?? null;
                $productName = $row[$indexMap['product_name']] ?? null;
                if (! $productCode || ! $productName) {
                    continue;
                }

                $payload = [
                    'sku' => trim((string) $productCode),
                    'product_name' => trim((string) $productName),
                    'category' => isset($indexMap['category']) ? ($row[$indexMap['category']] ?? null) : null,
                    'price' => isset($indexMap['price']) ? ($row[$indexMap['price']] ?? null) : null,
                    'expected_life_cycles' => isset($indexMap['expected_life_cycles']) ? ($row[$indexMap['expected_life_cycles']] ?? null) : null,
                    'description' => isset($indexMap['description']) ? ($row[$indexMap['description']] ?? null) : null,
                    'status' => 1,
                    'location_id' => $request->input('user_location_id'),
                ];

                $existing = Product::where('sku', $payload['sku'])->first();

                if ($existing) {
                    $existing->update($payload);
                } else {
                    Product::create($payload);
                }
                $processed++;

            }

            DB::commit();

            return response()->json(['success' => true, 'message' => "Bulk upload processed. Rows handled: {$processed}"]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('bulkProductUpload error: '.$e->getMessage());

            return response()->json(['success' => false, 'message' => 'Bulk upload failed.']);
        }
    }
}
