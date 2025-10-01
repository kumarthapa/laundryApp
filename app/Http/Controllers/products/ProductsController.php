<?php

namespace App\Http\Controllers\products;

use App\Exports\ProductExport;
use App\Helpers\FileImportHelper;
use App\Helpers\LocaleHelper;
use App\Helpers\TableHelper;
use App\Helpers\UtilityHelper;
use App\Http\Controllers\Controller;
use App\Models\products\ProductProcessHistory;
use App\Models\products\Products;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;

class ProductsController extends Controller
{
    protected $products;

    protected $stageMap = [];

    protected $defectPointMap = [];

    protected $statusMap = [];

    public function __construct()
    {
        $this->products = new Products;

        // Load config maps once for reuse (value => label)
        $configData = UtilityHelper::getProductStagesAndDefectPoints() ?? [];

        // stages: array of {name, value}
        $stages = $configData['stages'] ?? [];
        $this->stageMap = collect($stages)->mapWithKeys(function ($s) {
            $label = $s['name'] ?? ($s['label'] ?? $s['value'] ?? '');

            return [$s['value'] => $label];
        })->toArray();

        // status: array of {name, value} OR keyed map
        $status = $configData['status'] ?? [];
        if (is_array($status) && array_values($status) !== $status) {
            // already associative map value=>label
            $this->statusMap = $status;
        } else {
            $this->statusMap = collect($status)->mapWithKeys(function ($s) {
                $label = $s['name'] ?? ($s['label'] ?? $s['value'] ?? '');

                return [$s['value'] => $label];
            })->toArray();
        }

        // defect_points: object keyed by stage => array[{name,value}]
        $defects = $configData['defect_points'] ?? [];
        $flatDefects = [];
        if (is_array($defects)) {
            foreach ($defects as $stageKey => $points) {
                if (! is_array($points)) {
                    continue;
                }
                foreach ($points as $p) {
                    $val = $p['value'] ?? null;
                    $name = $p['name'] ?? ($p['label'] ?? $val);
                    if ($val) {
                        $flatDefects[$val] = $name;
                    }
                }
            }
        }
        $this->defectPointMap = $flatDefects;
    }

    public function index(Request $request)
    {
        // $productsOverview = UtilityHelper::getProductStagesAndStatus();
        // print_r($productsOverview);
        // exit;
        $headers = [
            ['created_at' => 'Created Date'],
            ['product_name' => 'Product Name'],
            ['sku' => 'SKU'],
            ['size' => 'Size'],
            // ['rfid_tag' => 'RFID Tag'],
            ['qa_code' => 'QA Code'],
            ['quantity' => 'Quantity'],
            ['qc_status' => 'QC Status'],
            ['current_stage' => 'Current Stage'],
            // ['actions' => 'Actions'],
        ];

        $productsOverview = LocaleHelper::getProductSummaryCounts();

        $productsOverview = [
            'total_products' => $productsOverview['total_products'] ?? 0,
            'total_qa_code' => $productsOverview['total_qa_code'] ?? 0,
            // 'total_tags' => $productsOverview['total_rfid_tags'] ?? 0,
            'total_pass_products' => $productsOverview['total_passed'] ?? 0,
            'total_fail_products' => $productsOverview['total_failed'] ?? 0,
            'total_rework_products' => $productsOverview['total_rework'] ?? 0,
            'total_pending_products' => $productsOverview['total_pending'] ?? 0,
        ];

        $pageConfigs = ['pageHeader' => true, 'isFabButton' => true];
        $currentUrl = $request->url();
        $UtilityHelper = new UtilityHelper;
        $createPermissions = $UtilityHelper::CheckModulePermissions('products', 'create.products');
        $deletePermissions = $UtilityHelper::CheckModulePermissions('products', 'delete.products');
        $table_headers = TableHelper::get_manage_table_headers($headers, true, false, true, true, true);

        $configData = UtilityHelper::getProductStagesAndDefectPoints();

        return view('content.products.list')
            ->with('pageConfigs', $pageConfigs)
            ->with('table_headers', $table_headers)
            ->with('currentUrl', $currentUrl)
            ->with('productsOverview', $productsOverview)
            ->with('stages', $configData['stages'] ?? [])
            ->with('defect_points', $configData['defect_points'] ?? [])
            ->with('status', $configData['status'] ?? [])
            ->with('deletePermissions', $deletePermissions)
            ->with('createPermissions', $createPermissions);
    }

    /**
     * Build a single row for datatable from DB row/stdClass
     */
    protected function tableHeaderRowData($row)
    {
        $data = [];
        $view = route('view.products', ['code' => $row->id]);
        $edit = route('edit.products', ['id' => $row->id]);
        $delete = route('delete.products', ['id' => $row->id]);

        // get latest history if present
        $history = ProductProcessHistory::where('product_id', $row->id)
            ->latest('changed_at')
            ->first();

        // QC status: prefer history.status, fallback to DB column (if exists)
        $statusRaw = $history->status ?? null;
        $statusNormalized = strtoupper(trim((string) ($statusRaw ?? '')));

        // normalize fail term variations
        if ($statusNormalized === 'FAIL') {
            $statusNormalized = 'FAIL';
        }

        $statusHTML = '';
        switch ($statusNormalized) {
            case 'PASS':
                $statusHTML = '<span class="badge rounded bg-label-success " title="PASS"><i class="icon-base bx bx-check-circle icon-lg me-1"></i>PASS</span>';
                break;
            case 'FAIL':
                $statusHTML = '<span class="badge rounded bg-label-danger " title="FAIL"><i class="icon-base bx bx-x-circle icon-lg me-1"></i>FAIL</span>';
                break;
            case 'REWORK':
                $statusHTML = '<span class="badge rounded bg-label-warning " title="REWORK"><i class="icon-base bx bx-refresh icon-lg me-1"></i>REWORK</span>';
                break;
            case 'PENDING':
            default:
                $statusHTML = '<span class="badge rounded bg-label-primary" title="PENDING"><i class="icon-base bx bx-time icon-lg me-1"></i>PENDING</span>';
                break;
        }

        // Stage name: prefer history.stages (value), map to friendly name if available
        $stageValue = $history->stages ?? null;
        $stageLabel = $this->stageMap[$stageValue] ?? $stageValue;
        $stageHTML = $stageLabel ? '<span class="badge rounded bg-label-secondary " title="Stage"><i class="icon-base bx bx-message-alt-detail me-1"></i>'.e($stageLabel).'</span>' : '';

        // Defect points (history.defects_points stored JSON array) -> display small badges or count
        $defectsHtml = '';
        $defectsCount = 0;
        $defectsRaw = $history->defects_points ?? null;
        if (! empty($defectsRaw)) {
            // try decode JSON safely
            $decoded = null;
            if (is_string($defectsRaw)) {
                $decoded = @json_decode($defectsRaw, true);
            } elseif (is_array($defectsRaw)) {
                $decoded = $defectsRaw;
            }
            if (is_array($decoded) && count($decoded) > 0) {
                $defectsCount = count($decoded);
                $pieces = [];
                foreach ($decoded as $d) {
                    $label = $this->defectPointMap[$d] ?? $d;
                    $pieces[] = '<span class="badge rounded bg-label-info me-1" title="'.e($label).'">'.e($label).'</span>';
                }
                $defectsHtml = implode(' ', $pieces);
            }
        }
        // Checkbox column (first cell) with data-id
        $data['checkbox'] = '<div class="form-check"><input type="checkbox" class="row-checkbox form-check-input" data-id="'.e($row->id).'"></div>';

        $data['created_at'] = LocaleHelper::formatDateWithTime($row->created_at);
        $data['product_name'] = $row->product_name;
        $data['sku'] = $row->sku;
        $data['size'] = $row->size;
        $data['qa_code'] = $row->qa_code;
        $data['quantity'] = $row->quantity;
        $data['qc_status'] = $statusHTML;
        $data['current_stage'] = $stageHTML.($defectsHtml ? '<div class="mt-1">'.$defectsHtml.'</div>' : '');

        // $data['actions'] = '<div class="d-inline-block">
        //     <a href="javascript:;" class="btn btn-sm text-primary btn-icon dropdown-toggle hide-arrow" data-bs-toggle="dropdown"><i class="bx bx-dots-vertical-rounded"></i></a>
        //     <ul class="dropdown-menu dropdown-menu-end">
        //         <li><a href="javascript:;" onclick="deleteRow(\''.$delete.'\');" class="dropdown-item text-danger delete-record"><i class="bx bx-trash me-1"></i>Delete</a></li>
        //     <div class="dropdown-divider"></div>
        //     </ul>
        // </div>';

        // <li><a href="'.$view.'" class="dropdown-item text-primary"><i class="bx bx-file me-1"></i>View Details</a></li>
        // <li><a href="'.$edit.'" class="dropdown-item text-primary item-edit"><i class="bx bxs-edit me-1"></i>Edit</a></li>
        return $data;
    }

    /* AJAX: return table rows */
    public function list(Request $request)
    {
        $search = $request->get('search') ?? '';
        $limit = intval($request->get('length', 100));
        $offset = intval($request->get('start', 0));
        $sort = $request->get('sort') ?? 'p.created_at';
        $order = $request->get('order') ?? 'desc';

        // Normalize incoming filter keys: support both old and new param names
        $filters = [
            // qc status may come as qc_status or status
            'status' => $request->get('qc_status') ?? $request->get('status') ?? '',
            // stage may come as current_stage or stages
            'stages' => $request->get('current_stage') ?? $request->get('stages') ?? '',
            // defect points param name might be defect_points (frontend) or defects_points (legacy/backward)
            'defects_points' => $request->get('defect_points') ?? $request->get('defects_points') ?? '',
        ];

        // normalize "ALL" to empty (no filter)
        foreach (['status', 'stages', 'defects_points'] as $k) {
            if (is_string($filters[$k]) && strtolower($filters[$k]) === 'all') {
                $filters[$k] = '';
            }
        }

        // If defect points sent as comma-separated string (from multi-select), convert to array
        if (! empty($filters['defects_points']) && is_string($filters['defects_points']) && strpos($filters['defects_points'], ',') !== false) {
            // trim values
            $arr = array_filter(array_map('trim', explode(',', $filters['defects_points'])));
            $filters['defects_points'] = array_values($arr);
        }

        // Date range
        $selectedDate = $request->get('selectedDaterange') ?? $request->get('default_dateRange');
        $daterange = LocaleHelper::dateRangeDateInputFormat($selectedDate);
        if ($daterange) {
            $filters['start_date'] = $daterange['start_date'] ?? '';
            $filters['end_date'] = $daterange['end_date'] ?? '';
        }

        $searchData = $this->products->search($search, $filters, $limit, $offset, $sort, $order);
        $total_rows = $this->products->get_found_rows($search, $filters);

        $data_rows = [];
        foreach ($searchData as $row) {
            $data_rows[] = $this->tableHeaderRowData($row);
        }

        $response = [
            'data' => $data_rows,
            'recordsTotal' => $total_rows,
            'recordsFiltered' => $total_rows,
        ];

        return response()->json($response);
    }

    public function create(Request $request)
    {
        return view('content.products.create', []);
    }

    public function edit(Request $request, $id)
    {
        $product = Products::find($id);
        if (! $product) {
            return view('content.miscellaneous.no-data');
        }

        return view('content.products.edit', ['product' => $product]);
    }

    public function save(Request $request, $id = null)
    {
        // Validation base rules
        $rules = [
            'product_name' => 'required|string|max:200',
            'sku' => 'required|string|max:100',
            'reference_code' => 'nullable|string|max:200',
            'size' => 'required|string|max:150',
            'rfid_tag' => 'required|string|max:200',
            'quantity' => 'nullable|integer|min:0',
        ];

        // Unique rules
        if ($id) {
            $rules['sku'] .= '|unique:products,sku,'.intval($id);
            $rules['rfid_tag'] .= '|unique:products,rfid_tag,'.intval($id);
        } else {
            $rules['sku'] .= '|unique:products,sku';
            $rules['rfid_tag'] .= '|unique:products,rfid_tag';
        }

        $messages = [
            'rfid_tag.unique' => 'The RFID Tag has already been taken.',
            'sku.unique' => 'The SKU has already been taken.',
        ];

        $validator = Validator::make($request->all(), $rules, $messages);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation fail',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $request->only([
            'product_name',
            'sku',
            'reference_code',
            'size',
            'rfid_tag',
            'quantity',
        ]);

        $user = Auth::user();
        $data['qc_status_updated_by'] = $user->id ?? null;

        DB::beginTransaction();
        try {
            if ($id) {
                $product = Products::findOrFail($id);
                $product->update($data);
                $action = 'update';
            } else {
                $product = Products::create($data);
                $action = 'create';
            }

            // Log activity
            $this->UserActivityLog($request, [
                'module' => 'products',
                'activity_type' => $action,
                'message' => ucfirst($action).'d product: '.$product->product_name,
                'application' => 'web',
                'data' => $data,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Product successfully '.($action === 'create' ? 'created' : 'updated').'.',
                'return_url' => route('products'),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Product '.($id ? 'update' : 'create').' error: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'An error occurred: '.$e->getMessage(),
            ], 500);
        }
    }

    /* Delete */
    public function delete(Request $request, $id = null)
    {
        // Collect ids from multiple possible sources: route param $id, request 'id', or request 'ids' (array)
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
            // print_r($ids);
            // exit;
            $models = Products::with('processHistory')->whereIn('id', $ids)->get();
            if ($models->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No matching records found.',
                ], 404);
            }

            $deletedCount = 0;
            $skipped = [];

            foreach ($models as $model) {
                // example rule: skip if written/locked
                if ($model->is_written ?? false) {
                    $skipped[] = $model->id;

                    continue;
                }

                // delete histories
                if (method_exists($model, 'processHistory')) {
                    $model->processHistory()->delete();
                }

                // delete related products if relation exists
                if (method_exists($model, 'products')) {
                    $model->products()->delete();
                }

                $model->delete();
                $deletedCount++;
            }

            DB::commit();

            // build message
            $message = $deletedCount.' item(s) deleted successfully.';
            if (! empty($skipped)) {
                $message .= ' Written items cannot be deleted: '.count($skipped).' item(s) skipped ['.implode(',', $skipped).'].';
            }

            // log user activity
            try {
                $user = Auth::user();
                $this->UserActivityLog($request, [
                    'module' => 'products',
                    'activity_type' => 'delete',
                    'message' => 'Delete action by: '.($user->fullname ?? 'Unknown'),
                    'application' => 'web',
                    'data' => [
                        'deleted_count' => $deletedCount,
                        'skipped' => $skipped,
                    ],
                ]);
            } catch (\Throwable $t) {
                Log::warning('UserActivityLog failed: '.$t->getMessage());
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'deleted' => $deletedCount,
                'skipped' => $skipped,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Delete exception: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Delete failed: '.$e->getMessage(),
            ], 500);
        }
    }

    /* Product import format (same as before) */
    public function productImportFormat(Request $request)
    {
        $data = [];
        try {
            $header = [
                'SKU',
                'Product Name',
                'Size',
                'Quantity',
                'RFID Tag',
                'Reference Code',
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
            }, 'productImportFormat.xlsx');
        } catch (\Exception $e) {
            return back()->with('error', 'There was an error generating the report: '.$e->getMessage());
        }
    }

    /* Bulk upload - simplified & robust */
    public function bulkProductUpload(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|mimes:xlsx,xls',
            'action_type' => 'required|in:upload_new,update_existing',
        ]);

        if ($validator->fails()) {
            Log::error('Product bulk upload fail: '.$validator->errors());

            return response()->json(['success' => false, 'errors' => $validator->errors(), 'message' => 'Invalid format for upload file or action type.']);
        }

        $actionType = $request->input('action_type');
        $file = $request->file('file');

        DB::beginTransaction();
        try {
            $formattedData = FileImportHelper::getFileData($file);
            if (! $formattedData || ! isset($formattedData['header']) || ! isset($formattedData['body']) || count($formattedData['body']) < 1) {
                return response()->json(['success' => false, 'message' => 'No product data found to upload.']);
            }

            $actualHeaders = $formattedData['header'];
            $missingHeaders = array_diff(['Product Name', 'SKU', 'Size'], $actualHeaders);
            if (count($missingHeaders) > 0) {
                return response()->json(['success' => false, 'message' => 'Missing mandatory header(s): '.implode(', ', $missingHeaders)]);
            }

            // Preload existing keys
            $existingSkus = Products::pluck('sku')->toArray();
            $existingRfids = Products::pluck('rfid_tag')->toArray();

            $fileSkus = [];
            $fileRfids = [];
            $importData = [];

            foreach ($formattedData['body'] as $rowIndex => $row) {
                if (empty($row) || (count(array_filter($row)) === 0)) {
                    continue; // skip empty rows
                }
                $productName = trim($row['Product Name'] ?? '');
                $sku = trim($row['SKU'] ?? '');
                $size = trim($row['Size'] ?? '');
                $rfidTag = trim($row['RFID Tag'] ?? '');
                $referenceCode = $row['Reference Code'] ?? null;
                $quantity = isset($row['Quantity']) ? (int) $row['Quantity'] : 0;

                if (empty($productName) || empty($sku) || empty($size)) {
                    return response()->json(['success' => false, 'message' => 'Missing required fields at row '.($rowIndex + 1).'. Product Name, SKU, and Size are mandatory.']);
                }

                if ($actionType === 'upload_new') {
                    if (in_array($sku, $existingSkus)) {
                        return response()->json(['success' => false, 'message' => "SKU '{$sku}' at row ".($rowIndex + 1).' already exists in database.']);
                    }
                    if ($rfidTag !== '' && in_array($rfidTag, $existingRfids)) {
                        return response()->json(['success' => false, 'message' => "RFID Tag '{$rfidTag}' at row ".($rowIndex + 1).' already exists in database.']);
                    }
                    if (in_array($sku, $fileSkus)) {
                        return response()->json(['success' => false, 'message' => "SKU '{$sku}' at row ".($rowIndex + 1).' is duplicated in the upload file.']);
                    }
                    if ($rfidTag !== '' && in_array($rfidTag, $fileRfids)) {
                        return response()->json(['success' => false, 'message' => "RFID Tag '{$rfidTag}' at row ".($rowIndex + 1).' is duplicated in the upload file.']);
                    }

                    $fileSkus[] = $sku;
                    if ($rfidTag !== '') {
                        $fileRfids[] = $rfidTag;
                    }
                } elseif ($actionType === 'update_existing') {
                    if (! Products::where('sku', $sku)->exists()) {
                        return response()->json(['success' => false, 'message' => "SKU '{$sku}' at row ".($rowIndex + 1).' does not exist for update.']);
                    }
                }

                $productData = [
                    'product_name' => $productName,
                    'sku' => $sku,
                    'reference_code' => $referenceCode,
                    'size' => $size,
                    'rfid_tag' => $rfidTag ?: null,
                    'quantity' => $quantity,
                    'qc_confirmed_at' => null,
                    'qc_status_updated_by' => null,
                    'updated_at' => now(),
                ];

                if ($actionType === 'upload_new') {
                    $productData['created_at'] = now();
                }

                $importData[] = $productData;
            }

            // Persist
            if (! empty($importData)) {
                if ($actionType === 'upload_new') {
                    Products::insert($importData);
                } else {
                    foreach ($importData as $p) {
                        Products::where('sku', $p['sku'])->update($p);
                    }
                }

                $this->UserActivityLog($request, [
                    'module' => 'products',
                    'activity_type' => $actionType === 'upload_new' ? 'bulk_upload_new' : 'bulk_update_existing',
                    'message' => $actionType === 'upload_new' ? 'Bulk upload of new products completed' : 'Bulk update of existing products completed',
                    'application' => 'web',
                    'data' => $importData,
                ]);
            }

            DB::commit();

            return response()->json(['success' => true, 'message' => 'Product data processed successfully.']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Product bulk upload error: '.$e->getMessage());

            return response()->json(['success' => false, 'message' => 'Bulk upload fail: '.$e->getMessage()]);
        }
    }

    public function exportProducts(Request $request)
    {
        $user = Auth::user();
        $daterange = $request->input('productsDaterangePicker');

        $metaInfo = [
            'date_range' => $daterange ?: 'All time',
            'generated_by' => $user->fullname ?? 'System',
        ];

        // Eager load latestHistory (we store stages/status as strings)
        $products = Products::with('latestHistory')->get();

        $dataRows = $products->map(function ($product) {
            $latest = $product->latestHistory;
            $latestStage = $latest ? ($latest->stages ?? '') : '';
            $latestStatus = $latest ? ($latest->status ?? '') : '';
            // normalize FAIL -> FAIL in export as well
            $latestStatusNormalized = strtoupper(trim((string) $latestStatus));
            if ($latestStatusNormalized === 'FAIL') {
                $latestStatusNormalized = 'FAIL';
            }

            return [
                $product->product_name,
                $product->sku,
                $product->reference_code,
                $product->size,
                $product->qa_code,
                $product->quantity,
                $latestStatusNormalized,
                $latestStage,
                $product->qc_confirmed_at ? LocaleHelper::formatDateWithTime($product->qc_confirmed_at) : '',
                LocaleHelper::formatDateWithTime($product->created_at),
                LocaleHelper::formatDateWithTime($product->updated_at),
            ];
        })->toArray();

        $headers = [
            'Product Name',
            'SKU',
            'Reference Code',
            'Size',
            'QA Code',
            'Quantity',
            'QC Status',
            'Current Stage',
            'QC Confirmed At',
            'Created At',
            'Updated At',
        ];

        return Excel::download(new ProductExport($dataRows, $metaInfo, $headers), 'products_export_'.now()->format('Ymd_His').'.xlsx');
    }

    public function exportProductsStageWise(Request $request)
    {
        $user = Auth::user();
        $daterange = $request->input('productsDaterangePicker');

        $metaInfo = [
            'date_range' => $daterange ?: 'All time',
            'generated_by' => $user->fullname ?? 'System',
        ];

        // Fetch stages dynamically from config
        $configData = UtilityHelper::getProductStagesAndDefectPoints();
        $stages = $configData['stages'] ?? [];

        // Base product headers (static part)
        $baseHeaders = [
            'SKU',
            'Product Name',
            'Size',
            'QA Code',
            'Reference Code',
        ];

        // Append stage-wise headers dynamically
        $stageHeaders = array_map(fn ($stage) => $stage['name'], $stages);

        // Meta/product info headers (could also be dynamic if needed)
        $extraHeaders = [
            // 'QC Confirmed At',
            'Created At',
            'Updated At',
        ];

        // Final headers
        $headers = array_merge($baseHeaders, $stageHeaders, $extraHeaders);

        // Fetch products with histories
        $products = Products::with('processHistory')->get();

        $dataRows = $products->map(function ($product) use ($stages) {
            // Start with base columns
            $row = [
                $product->sku,
                $product->product_name,
                $product->size,
                $product->qa_code,
                $product->reference_code,
            ];

            // Collect stage-wise statuses
            $historyByStage = $product->processHistory->keyBy('stages');

            foreach ($stages as $stage) {
                $stageKey = $stage['value']; // e.g. bonding_qc
                $status = isset($historyByStage[$stageKey])
                    ? strtoupper($historyByStage[$stageKey]->status)
                    : '';
                $row[] = $status;
            }

            // Append extra fields
            // $row[] = $product->qc_confirmed_at ? LocaleHelper::formatDateWithTime($product->qc_confirmed_at) : '';
            $row[] = LocaleHelper::formatDateWithTime($product->created_at);
            $row[] = LocaleHelper::formatDateWithTime($product->updated_at);

            return $row;
        })->toArray();

        return Excel::download(
            new ProductExport($dataRows, $metaInfo, $headers),
            'products_stage_wise_export_'.now()->format('Ymd_His').'.xlsx'
        );
    }
}
