<?php

namespace App\Http\Controllers\products;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use App\Models\user_management\Role;

use App\Helpers\TableHelper;
use App\Helpers\LocaleHelper;
use App\Helpers\FileImportHelper;
use App\Helpers\UtilityHelper;
use App\Helpers\ConfigHelper;

use Exception;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

use App\Models\user_management\UsersModel;
use App\Exports\ProductExport;

use App\Models\products\Products;
use App\Models\products\ProductProcessHistory;
use App\Models\products\BondingPlanProduct;

class BondingPlanProductController extends Controller
{
    protected $bondingPlanProduct;
    protected $stageMap = [];
    protected $defectPointMap = [];
    protected $statusMap = [];

    public function __construct()
    {
        $this->bondingPlanProduct = new BondingPlanProduct();

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
                if (!is_array($points)) continue;
                foreach ($points as $p) {
                    $val = $p['value'] ?? null;
                    $name = $p['name'] ?? ($p['label'] ?? $val);
                    if ($val) $flatDefects[$val] = $name;
                }
            }
        }
        $this->defectPointMap = $flatDefects;
    }

    public function index(Request $request)
    {
        $headers = [
            ['created_at' => 'Created Date'],
            ['product_name' => 'Product Name'],
            ['model' => 'Model'],
            ['sku' => 'SKU'],
            ['size' => 'Size'],
            ['qa_code' => 'QA Code'],
            ['rfid_tag' => 'RFID Tag'],
            ['is_write' => 'Is Write'],
            // ['quantity' => 'Quantity'],
            // ['qc_status' => 'QC Status'],
            // ['current_stage' => 'Current Stage'],
            ['actions' => 'Actions']
        ];

        $productsOverview = LocaleHelper::getProductSummaryCounts();

        $productsOverview = [
            'total_products'         => $productsOverview['total_products'] ?? 0,
            'total_tags'             => $productsOverview['total_rfid_tags'] ?? 0,
            'total_pass_products'    => $productsOverview['total_pass'] ?? 0,
            'total_failed_products'  => $productsOverview['total_failed'] ?? 0,
            'total_rework_products'  => $productsOverview['total_rework'] ?? 0,
            'total_pending_products' => $productsOverview['total_pending'] ?? 0,
        ];

        $pageConfigs = ['pageHeader' => true, 'isFabButton' => true];
        $currentUrl = $request->url();
        $UtilityHelper = new UtilityHelper();
        $createPermissions = $UtilityHelper::CheckModulePermissions('bonding', 'create.bonding');
        $table_headers = TableHelper::get_manage_table_headers($headers, true, true, true);

        $configData = UtilityHelper::getProductStagesAndDefectPoints();

        return view('content.bonding.list')
            ->with('pageConfigs', $pageConfigs)
            ->with('table_headers', $table_headers)
            ->with('currentUrl', $currentUrl)
            ->with('productsOverview', $productsOverview)
            ->with('stages', $configData['stages'] ?? [])
            ->with('defect_points', $configData['defect_points'] ?? [])
            ->with('status', $configData['status'] ?? [])
            ->with('createPermissions', $createPermissions);
    }

    /**
     * Build a single row for datatable from DB row/stdClass
     */
    protected function tableHeaderRowData($row)
    {
        $data = [];
        $view = route('view.bonding', ["code" => $row->id]);
        $edit = route('edit.bonding', ["id" => $row->id]);
        $delete = route('delete.bonding', ["id" => $row->id]);

        // get latest history if present
        $history = ProductProcessHistory::where('product_id', $row->id)
            ->latest('changed_at')
            ->first();

        // QC status: prefer history.status, fallback to DB column (if exists)
        $statusRaw = $history->status ?? ($row->qc_status ?? null);
        $statusNormalized = strtoupper(trim((string)($statusRaw ?? '')));

        // normalize failed term variations
        if ($statusNormalized === 'FAILED') {
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
        $stageValue = $history->stages ?? 'BONDING';
        $stageLabel = $this->stageMap[$stageValue] ?? $stageValue;
        $stageHTML = $stageLabel ? '<span class="badge rounded bg-label-secondary " title="Stage"><i class="icon-base bx bx-message-alt-detail me-1"></i>' . e($stageLabel) . '</span>' : '';

        // Defect points (history.defects_points stored JSON array) -> display small badges or count
        $defectsHtml = '';
        $defectsCount = 0;
        $defectsRaw = $history->defects_points ?? null;
        if (!empty($defectsRaw)) {
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
                    $pieces[] = '<span class="badge rounded bg-label-info me-1" title="' . e($label) . '">' . e($label) . '</span>';
                }
                $defectsHtml = implode(' ', $pieces);
            }
        }

        $data['created_at'] = LocaleHelper::formatDateWithTime($row->created_at);
        $data['product_name'] = $row->product_name;
        $data['model'] = $row->model ?? 'N/A';
        $data['sku'] = $row->sku;
        $data['size'] = $row->size;
        $data['qa_code'] = $row->qa_code;
        $data['rfid_tag'] = $row->rfid_tag ?? 'N/A';
        $data['is_write'] = $row->is_write == 0 ? 'PENDING' : 'WRITTEN';
        // $data['quantity'] = $row->quantity;
        // $data['qc_status'] = $statusHTML;
        // $data['current_stage'] = $stageHTML . ($defectsHtml ? '<div class="mt-1">' . $defectsHtml . '</div>' : '');

        $data['actions'] = '<div class="d-inline-block">
            <a href="javascript:;" class="btn btn-sm text-primary btn-icon dropdown-toggle hide-arrow" data-bs-toggle="dropdown"><i class="bx bx-dots-vertical-rounded"></i></a>
            <ul class="dropdown-menu dropdown-menu-end">
                <li><a href="' . $view . '" class="dropdown-item text-primary"><i class="bx bx-file me-1"></i>View Details</a></li>
                <li><a href="' . $edit . '" class="dropdown-item text-primary item-edit"><i class="bx bxs-edit me-1"></i>Edit</a></li>
                <li><a href="javascript:;" onclick="deleteRow(\'' . $delete . '\');" class="dropdown-item text-danger delete-record"><i class="bx bx-trash me-1"></i>Delete</a></li>
            <div class="dropdown-divider"></div>
            </ul>
        </div>';

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
        if (!empty($filters['defects_points']) && is_string($filters['defects_points']) && strpos($filters['defects_points'], ',') !== false) {
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

        $searchData = $this->bondingPlanProduct->search($search, $filters, $limit, $offset, $sort, $order);
        $total_rows = $this->bondingPlanProduct->get_found_rows($search, $filters);

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
        return view('content.bonding.create', []);
    }

    public function edit(Request $request, $id)
    {
        $product = BondingPlanProduct::find($id);
        if (!$product) {
            return view('content.miscellaneous.no-data');
        }
        return view('content.bonding.edit', ['product' => $product]);
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
            $rules['sku'] .= '|unique:bonding,sku,' . intval($id);
            $rules['rfid_tag'] .= '|unique:bonding,rfid_tag,' . intval($id);
        } else {
            $rules['sku'] .= '|unique:bonding,sku';
            $rules['rfid_tag'] .= '|unique:bonding,rfid_tag';
        }

        $messages = [
            'rfid_tag.unique' => 'The RFID Tag has already been taken.',
            'sku.unique' => 'The SKU has already been taken.',
        ];

        $validator = Validator::make($request->all(), $rules, $messages);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
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
                $product = BondingPlanProduct::findOrFail($id);
                $product->update($data);
                $action = 'update';
            } else {
                $product = BondingPlanProduct::create($data);
                $action = 'create';
            }

            // Log activity
            $this->UserActivityLog($request, [
                'module' => 'bonding',
                'activity_type' => $action,
                'message' => ucfirst($action) . "d product: " . $product->product_name,
                'application' => 'web',
                'data' => $data,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Product successfully " . ($action === 'create' ? "created" : "updated") . ".",
                'return_url' => route('bonding'),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Product ' . ($id ? 'update' : 'create') . ' error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'An error occurred: ' . $e->getMessage(),
            ], 500);
        }
    }

    /* Delete */
    public function delete(Request $request, $id = '')
    {
        $delete_id = $id ?: $request->input('id');
        $Model = BondingPlanProduct::find($delete_id);
        if (!$Model) {
            return response()->json([
                'success' => false,
                'message' => 'Delete Failed! Product not found.',
                'bg_color' => 'bg-danger'
            ]);
        }

        try {
            // remove related histories and then product
            $Model->processHistory()->delete();
            $Model->delete();

            $user = Auth::user();
            $this->UserActivityLog($request, [
                'module' => 'bonding',
                'activity_type' => 'delete',
                'message' => 'Deleted product by : ' . ($user->fullname ?? 'Unknown'),
                'application' => 'web',
                'data' => [
                    'sku' => $Model->sku,
                    'rfid_tag' => $Model->rfid_tag
                ]
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Record and related histories deleted successfully',
                'bg_color' => 'bg-success'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Delete Failed! ' . $e->getMessage(),
                'bg_color' => 'bg-danger'
            ]);
        }
    }
    public function exportBonding(Request $request)
    {
        $user = Auth::user();
        $daterange = $request->input('bondingDaterangePicker');

        $metaInfo = [
            'date_range' => $daterange ?: 'All time',
            'generated_by' => $user->fullname ?? 'System',
        ];

        // Eager load latestHistory (we store stages/status as strings)
        $products = BondingPlanProduct::with('latestHistory')->get();

        $dataRows = $products->map(function ($product) {
            $latest = $product->latestHistory;
            $latestStage = $latest ? ($latest->stages ?? '') : ($product->current_stage ?? '');
            $latestStatus = $latest ? ($latest->status ?? '') : ($product->qc_status ?? '');
            // normalize FAILED -> FAIL in export as well
            $latestStatusNormalized = strtoupper(trim((string)$latestStatus));
            if ($latestStatusNormalized === 'FAILED') $latestStatusNormalized = 'FAIL';

            return [
                $product->product_name,
                $product->sku,
                $product->reference_code,
                $product->size,
                $product->rfid_tag,
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
            'RFID Tag',
            'Quantity',
            'QC Status',
            'Current Stage',
            'QC Confirmed At',
            'Created At',
            'Updated At',
        ];

        return Excel::download(new ProductExport($dataRows, $metaInfo, $headers), 'bonding_export_' . now()->format('Ymd_His') . '.xlsx');
    }

    // ============================ Bonding section ==========================
    public function bondingPlanImportFormat(Request $request)
    {
        try {
            $header = [
                'SKU',
                'Product Name',
                'Size',
                'Reference Code',
            ];

            $data = [$header, []];

            return Excel::download(
                new class($data) implements \Maatwebsite\Excel\Concerns\FromArray {
                    protected $data;
                    public function __construct(array $data)
                    {
                        $this->data = $data;
                    }
                    public function array(): array
                    {
                        return $this->data;
                    }
                },
                'bondingFormat.xlsx'
            );
        } catch (\Exception $e) {
            return back()->with('error', 'Error generating format: ' . $e->getMessage());
        }
    }

    public function bulkBondingPlanUpload(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|mimes:xlsx,xls',
            'action_type' => 'required|in:upload_new,update_existing',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
                'message' => 'Invalid file or action type.'
            ]);
        }

        $actionType = $request->input('action_type');
        $file = $request->file('file');

        DB::beginTransaction();
        try {
            $formattedData = FileImportHelper::getFileData($file);
            if (
                !$formattedData ||
                !isset($formattedData['header']) ||
                !isset($formattedData['body']) ||
                count($formattedData['body']) < 1
            ) {
                return response()->json(['success' => false, 'message' => 'No data found in file.']);
            }

            $actualHeaders = $formattedData['header'];
            $missingHeaders = array_diff(['Product Name', 'SKU', 'Size'], $actualHeaders);
            if (count($missingHeaders) > 0) {
                return response()->json(['success' => false, 'message' => 'Missing headers: ' . implode(', ', $missingHeaders)]);
            }

            // Existing DB values
            $existingSkus = BondingPlanProduct::pluck('sku')->toArray();
            $fileSkus = [];
            $importData = [];

            foreach ($formattedData['body'] as $rowIndex => $row) {
                if (empty($row) || (count(array_filter($row)) === 0)) {
                    continue; // skip empty rows
                }
                if (empty(trim($row['Product Name'] ?? '')) || empty(trim($row['Size'] ?? ''))) {
                    return response()->json(['success' => false, 'message' => "Row " . ($rowIndex + 1) . ": Missing required fields. Product Name and Size are mandatory."]);
                }

                $productName   = trim($row['Product Name'] ?? '');
                $sku           = trim($row['SKU'] ?? '') ?: null; // now nullable
                $size          = trim($row['Size'] ?? '');
                $referenceCode = $row['Reference Code'] ?? null;
                $Model         = $row['Model'] ?? ($sku ?? 'N/A');

                if ($actionType === 'upload_new') {
                    if ($sku && in_array($sku, $existingSkus)) {
                        return response()->json(['success' => false, 'message' => "Row " . ($rowIndex + 1) . ": SKU '{$sku}' already exists."]);
                    }
                    if ($sku && in_array($sku, $fileSkus)) {
                        return response()->json(['success' => false, 'message' => "Row " . ($rowIndex + 1) . ": Duplicate SKU '{$sku}' in file."]);
                    }
                    if ($sku) {
                        $fileSkus[] = $sku;
                    }
                } elseif ($actionType === 'update_existing') {
                    if ($sku && !BondingPlanProduct::where('sku', $sku)->exists()) {
                        return response()->json(['success' => false, 'message' => "Row " . ($rowIndex + 1) . ": SKU '{$sku}' does not exist for update."]);
                    }
                }

                // Example QA code generator
                $modelCode = $this->getModelFromProductName($productName);
                $date  = (int)date('d');
                $month = (int)date('m');
                $year  = (int)date('y');
                $qa_code = "{$modelCode}-{$size}-{$date}{$month}{$year}";

                $productData = [
                    'product_name'   => $productName,
                    'sku'            => $sku,
                    'reference_code' => $referenceCode,
                    'size'           => $size,
                    'model'          => $Model,
                    'qa_code'        => $qa_code,
                    'date'           => $date,
                    'month'          => $month,
                    'year'           => $year,
                    'serial_no'      => $row['Serial No'] ?? null,
                    'bonding_name'   => $row['Bonding Name'] ?? null,
                ];

                if ($actionType === 'upload_new') {
                    $productData['created_at'] = now();
                }

                $importData[] = $productData;
            }

            // Save to DB
            if (!empty($importData)) {
                if ($actionType === 'upload_new') {
                    BondingPlanProduct::insert($importData);
                } else {
                    foreach ($importData as $p) {
                        BondingPlanProduct::where('sku', $p['sku'])->update($p);
                    }
                }
            }

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Bonding plan product data processed successfully.']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Upload failed: ' . $e->getMessage()]);
        }
    }

    public function getModelFromProductName($productName)
    {
        $words = preg_split('/\s+/', trim($productName));
        $model = '';
        foreach ($words as $word) {
            if (ctype_alpha($word[0])) {
                $model .= strtoupper($word[0]);
            }
            if (strlen($model) >= 3) break;
        }
        return $model ?: 'N/A';
    }
}