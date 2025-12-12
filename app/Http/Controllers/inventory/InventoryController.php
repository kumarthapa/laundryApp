<?php

namespace App\Http\Controllers\inventory;

use App\Exports\ProductExport;
use App\Helpers\FileImportHelper;
use App\Helpers\LocaleHelper;
use App\Helpers\TableHelper;
use App\Helpers\UtilityHelper;
use App\Http\Controllers\Controller;
use App\Models\inventory\Inventory;
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

class InventoryController extends Controller
{
    protected $Inventory;

    protected $stageMap = [];

    protected $defectPointMap = [];

    protected $statusMap = [];

    public function __construct()
    {
        $this->Inventory = new Inventory;
    }

    public function index(Request $request)
    {
        $authUser = Auth::user();
        $role_info = Role::find($authUser->role_id);

        $headers = [
            ['epc_code' => 'Tag'],
            // ['tag_code' => 'Tag Code'],
            ['location_id' => 'Location'],
            ['status' => 'Status'],
            ['life_cycles' => 'Life Cycles'],
            ['mapped_at' => 'Mapped At'],
            ['last_scanned_at' => 'Last Scanned At'],
        ];

        $locations_info = [];
        if ($role_info->role_type == 'super_role' || $role_info->role_type == 'admin_role') {
            $locations_info = Location::all();
        }

        $pageConfigs = ['pageHeader' => true, 'isFabButton' => true];
        $currentUrl = $request->url();
        $UtilityHelper = new UtilityHelper;
        $createPermissions = $UtilityHelper::CheckModulePermissions('inventory', 'create.inventory');
        $deletePermissions = $UtilityHelper::CheckModulePermissions('inventory', 'delete.inventory');

        $table_headers = TableHelper::get_manage_table_headers($headers, true, false, true, true, true);

        // --- INVENTORY METRICS ---
        $inventoryOverview = [
            'total_inventory' => Inventory::count(),
            'total_new' => Inventory::where('status', 'new')->count(),
            'total_clean' => Inventory::where('status', 'clean')->count(),
            'total_dirty' => Inventory::where('status', 'dirty')->count(),
        ];

        return view('content.inventory.list')
            ->with('pageConfigs', $pageConfigs)
            ->with('table_headers', $table_headers)
            ->with('currentUrl', $currentUrl)
            ->with('inventoryOverview', $inventoryOverview)
            ->with('createPermissions', $createPermissions)
            ->with('locations_info', $locations_info)
            ->with('deletePermissions', $deletePermissions);
    }

    /**
     * Build a single row for datatable from DB row/stdClass
     */
    // protected function tableHeaderRowData($row)
    // {
    //     $data = [];
    //     // $view = route('view.inventory', ['code' => $row->id]);

    //     $edit = route('edit.inventory', ['id' => $row->id]);
    //     $delete = route('delete.inventory', ['id' => $row->id]);

    //     $statusHTML = '';
    //     if ($row->is_write) {
    //         $statusHTML = '<span class="badge rounded bg-label-success " title="WRITTEN"><i class="icon-base bx bx-check-circle icon-lg me-1"></i>WRITTEN</span>';
    //     } else {
    //         $statusHTML = '<span class="badge rounded bg-label-warning " title="PENDING"><i class="icon-base bx bx-refresh icon-lg me-1"></i>PENDING</span>';
    //     }

    //     // Stage name: prefer history.stages (value), map to friendly name if available
    //     // $stageValue = $history->stages ?? 'bonding_qc';
    //     // $stageLabel = $this->stageMap[$stageValue] ?? $stageValue;
    //     // $stageHTML = $stageLabel ? '<span class="badge rounded bg-label-secondary " title="Stage"><i class="icon-base bx bx-message-alt-detail me-1"></i>'.e($stageLabel).'</span>' : '';

    //     // Defect points (history.defects_points stored JSON array) -> display small badges or count
    //     // $defectsHtml = '';
    //     // $defectsCount = 0;

    //     // $defectsRaw = $history->defects_points ?? null;
    //     // if (! empty($defectsRaw)) {
    //     //     // try decode JSON safely
    //     //     $decoded = null;
    //     //     if (is_string($defectsRaw)) {
    //     //         $decoded = @json_decode($defectsRaw, true);
    //     //     } elseif (is_array($defectsRaw)) {
    //     //         $decoded = $defectsRaw;
    //     //     }
    //     //     if (is_array($decoded) && count($decoded) > 0) {
    //     //         $defectsCount = count($decoded);
    //     //         $pieces = [];
    //     //         foreach ($decoded as $d) {
    //     //             $label = $this->defectPointMap[$d] ?? $d;
    //     //             $pieces[] = '<span class="badge rounded bg-label-info me-1" title="'.e($label).'">'.e($label).'</span>';
    //     //         }
    //     //         $defectsHtml = implode(' ', $pieces);
    //     //     }
    //     // }

    //     $data['created_at'] = LocaleHelper::formatDateWithTime($row->created_at);
    //     $data['product_name'] = $row->product_name;
    //     $data['model'] = $row->model ?? 'N/A';
    //     $data['sku'] = $row->sku;
    //     $data['size'] = $row->size;
    //     $data['rfid_code'] = $row->rfid_code;
    //     // $data['rfid_tag'] = $row->rfid_tag ?? 'N/A';
    //     $data['is_write'] = $statusHTML;
    //     // $data['quantity'] = $row->quantity;

    //     $data['actions'] = '<div class="d-inline-block">
    //             <a href="javascript:;" class="btn btn-sm text-primary btn-icon dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
    //             <i class="bx bx-dots-vertical-rounded"></i></a>
    //             <ul class="dropdown-menu dropdown-menu-end">
    //                 <li><a href="javascript:;" onclick="deleteRow(\''.$delete.'\');" class="dropdown-item text-danger delete-record"><i class="bx bx-trash me-1"></i>Delete</a></li>
    //             <div class="dropdown-divider"></div>
    //             </ul>
    //         </div>';

    //     return $data;

    //     // <li><a href="javascripts:;" class="dropdown-item text-primary"><i class="bx bx-file me-1"></i>View Details</a></li>
    //     // <li><a href="'.$edit.'" class="dropdown-item text-primary item-edit"><i class="bx bxs-edit me-1"></i>Edit</a></li>
    // }
    protected function tableHeaderRowData($row)
    {
        $data = [];

        // Checkbox column
        $data['checkbox'] =
            '<div class="form-check">
            <input type="checkbox" class="row-checkbox form-check-input" data-id="'.e($row->id).'">
        </div>';

        // Basic fields
        $data['epc_code'] = e($row->epc_code);
        // $data['tag_code'] = e($row->tag_code);
        $data['location_id'] = LocaleHelper::getLocationNameById($row->location_id);

        // Status badge formatting
        $data['status'] = $this->getStatusBadge($row->status);
        $data['life_cycles'] = e($row->life_cycles);
        // Dates
        $data['mapped_at'] = LocaleHelper::formatDateWithTime($row->mapped_at);
        $data['last_scanned_at'] = LocaleHelper::formatDateWithTime($row->last_scanned_at);

        // rfid_code (fallback empty)
        $data['rfid_code'] = e($row->rfid_code ?? '');

        return $data;
    }

    /**
     * Format status as colored badge.
     */
    protected function getStatusBadge($status)
    {
        $colors = [
            'new' => 'success',
            'clean' => 'success',
            'dirty' => 'danger',
            'out' => 'dark',
            'lost' => 'secondary',
            'damaged' => 'danger',
        ];

        $label = ucfirst($status);
        $color = $colors[$status] ?? 'primary';

        return '<span class="badge bg-label-'.$color.'">'.$label.'</span>';
    }

    /* AJAX: return table rows */
    public function list(Request $request)
    {
        $search = $request->get('search') ?? '';
        $limit = intval($request->get('length', 10000));
        $offset = intval($request->get('start', 0));
        $sort = $request->get('sort') ?? 'created_at';
        $order = $request->get('order') ?? 'desc';

        // Normalize incoming filter keys: support both old and new param names
        $filters = [
            // qc status may come as qa_status or status
            'status' => $request->get('status') ?? 'all',
        ];

        // Date range
        $selectedDate = $request->get('selectedDaterange') ?? $request->get('default_dateRange');
        $daterange = LocaleHelper::dateRangeDateInputFormat($selectedDate);
        $filters['start_date'] = $daterange['start_date'] ?? Carbon::today()->subDays(29)->startOfDay();
        $filters['end_date'] = $daterange['end_date'] ?? Carbon::today()->endOfDay();

        $searchData = $this->Inventory->search($search, $filters, $limit, $offset, $sort, $order);
        $total_rows = $this->Inventory->get_found_rows($search, $filters);

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
        return view('content.inventory.create', []);
    }

    public function edit(Request $request, $id)
    {
        $product = Inventory::find($id);
        if (! $product) {
            return view('content.miscellaneous.no-data');
        }

        return view('content.inventory.edit', ['product' => $product]);
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
            $rules['sku'] .= '|unique:bonding,sku,'.intval($id);
            $rules['rfid_tag'] .= '|unique:bonding,rfid_tag,'.intval($id);
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
                $product = Inventory::findOrFail($id);
                $product->update($data);
                $action = 'update';
            } else {
                $product = Inventory::create($data);
                $action = 'create';
            }

            // Log activity
            $this->UserActivityLog($request, [
                'module' => 'inventory',
                'activity_type' => $action,
                'message' => ucfirst($action).'d product: '.$product->product_name,
                'application' => 'web',
                'data' => $data,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Product successfully '.($action === 'create' ? 'created' : 'updated').'.',
                'return_url' => route('inventory'),
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

    public function delete(Request $request, $id = null)
    {
        // Collect ids from multiple possible sources: route param $id, request 'id', or request 'ids' (array)
        $inputIds = $request->input('ids', null);
        $singleId = $id ?: $request->input('id', null);

        if (is_array($inputIds) && ! empty($inputIds)) {
            $ids = array_values(array_filter($inputIds, function ($v) {
                return ! is_null($v) && $v !== '';
            }));
        } elseif ($singleId) {
            $ids = [$singleId];
        } else {
            return response()->json(['success' => false, 'message' => 'No id(s) provided.'], 422);
        }

        Log::info('Delete requested for bonding ids: '.json_encode($ids));
        DB::beginTransaction();
        try {

            $query = Inventory::with('products')->whereIn('id', $ids);
            $models = LocaleHelper::commonWhereLocationCheck($query, 'rfid_tags');
            $models = $models->get();

            if ($models->isEmpty()) {
                return response()->json(['success' => false, 'message' => 'No matching records found.'], 404);
            }

            $deletedCount = 0;
            $skipped = [];
            foreach ($models as $model) {
                // if ($model->is_write) {
                //     $skipped[] = $model->id;

                //     continue;
                // }
                // Delete related product process history if method exists
                foreach ($model->products as $product) {
                    if (method_exists($product, 'processHistory')) {
                        $product->processHistory()->delete();
                    }
                }

                // Delete related products and the model
                $model->products()->delete();
                $model->delete();
                $deletedCount++;
            }

            DB::commit();
            Log::info('item(s) deleted successfully count: '.json_encode($deletedCount));
            $message = $deletedCount.' item(s) deleted successfully.';
            // if (! empty($skipped)) {
            //     $message .= ' Written items cannot be deleted: '.count($skipped).' item(s) skipped ['.implode(',', $skipped).'].';
            // }
            // 0 item(s) deleted successfully. Skipped 1 written item(s): [80].
            // optional: log user activity
            try {
                $user = Auth::user();
                $this->UserActivityLog($request, [
                    'module' => 'inventory',
                    'activity_type' => 'delete',
                    'message' => 'Bulk delete by: '.($user->fullname ?? 'Unknown'),
                    'application' => 'web',
                    'data' => ['deleted_count' => $deletedCount, 'skipped' => $skipped],
                ]);
            } catch (\Throwable $t) {
                Log::warning('UserActivityLog failed: '.$t->getMessage());
            }

            return response()->json(['success' => true, 'message' => $message]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Delete exception: '.$e->getMessage());

            return response()->json(['success' => false, 'message' => 'Delete failed: '.$e->getMessage()], 500);
        }
    }

    public function exportInventory(Request $request)
    {
        $user = Auth::user();

        // Accept from POST body or query string
        $daterange = $request->input('daterange');
        $status = $request->input('status');

        $startDate = null;
        $endDate = null;

        // -----------------------------
        // Parse Date Range (DD/MM/YYYY - DD/MM/YYYY)
        // -----------------------------
        if (! empty($daterange)) {
            try {
                [$start, $end] = array_map('trim', explode('-', $daterange));

                $startDate = \Carbon\Carbon::createFromFormat('d/m/Y', $start)->startOfDay();
                $endDate = \Carbon\Carbon::createFromFormat('d/m/Y', $end)->endOfDay();
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => "Invalid date range format. Expected 'DD/MM/YYYY - DD/MM/YYYY'.",
                ]);
            }
        }

        // -----------------------------
        // Meta info for export sheet
        // -----------------------------
        $metaInfo = [
            'date_range' => $daterange ?: 'All time',
            'generated_by' => $user->fullname ?? 'System',
        ];

        // -----------------------------
        // Build Query
        // -----------------------------
        $query = Inventory::with('products');
        $query = LocaleHelper::commonWhereLocationCheck($query, 'rfid_tags'); // location filter

        // Date filter
        if ($startDate && $endDate) {
            $query->whereBetween('created_at', [$startDate, $endDate]);
        }

        // Status filter
        if ($status !== null && $status !== '' && $status !== 'all') {
            $query->where('status', $status);
        }

        $inventoryList = $query->get();

        // -----------------------------
        // Prepare export rows
        // -----------------------------
        $dataRows = $inventoryList->map(function ($tag) {
            return [
                $tag->epc_code,
                $tag->reader_type,
                $tag->reader_code,
                $tag->product_id,
                $tag->products->product_name ?? 'N/A',
                $tag->products->sku ?? 'N/A',
                LocaleHelper::getLocationNameById($tag->location_id),
                $tag->status,
                LocaleHelper::formatDateWithTime($tag->mapped_at),
                LocaleHelper::formatDateWithTime($tag->last_scanned_at),
                LocaleHelper::formatDateWithTime($tag->created_at),
                LocaleHelper::formatDateWithTime($tag->updated_at),
            ];
        })->toArray();

        // -----------------------------
        // Headers
        // -----------------------------
        $headers = [
            'Tag',
            'Reader Type',
            'Reader Code',
            'Product ID',
            'Product Name',
            'SKU',
            'Location ID',
            'Status',
            'Mapped At',
            'Last Scanned At',
            'Created At',
            'Updated At',
        ];

        // -----------------------------
        // Download Excel
        // -----------------------------
        return Excel::download(
            new ProductExport($dataRows, $metaInfo, $headers),
            'inventory_export_'.now()->format('Ymd_His').'.xlsx'
        );
    }

    // ============================ Bonding section ==========================

    public function bondingPlanImportFormat(Request $request)
    {
        try {
            $header = [
                'SKU',               // optional
                'Product Name',
                'Size',
                'QTY',               // main driver: number of rows to generate
                'Reference Code',
                'QC Confirmed At',   // expected format: 09/13/2025 20:57:39 (MM/DD/YYYY HH:mm:ss)
            ];

            $data = [$header, []];

            return Excel::download(
                new class($data) implements \Maatwebsite\Excel\Concerns\FromArray
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
                },
                'bondingFormat.xlsx'
            );
        } catch (\Exception $e) {
            return back()->with('error', 'Error generating format: '.$e->getMessage());
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
                'message' => 'Invalid file or action type.',
            ]);
        }

        $actionType = $request->input('action_type');
        $location_id = $request->input('user_location_id');
        $file = $request->file('file');

        DB::beginTransaction();
        try {
            $formattedData = FileImportHelper::getFileData($file);
            if (
                ! $formattedData ||
                ! isset($formattedData['header']) ||
                ! isset($formattedData['body']) ||
                count($formattedData['body']) < 1
            ) {
                return response()->json(['success' => false, 'message' => 'No data found in file.']);
            }

            $actualHeaders = $formattedData['header'];

            // Required headers
            $missingHeaders = array_diff(['Product Name', 'Size', 'QTY'], $actualHeaders);
            if (count($missingHeaders) > 0) {
                return response()->json(['success' => false, 'message' => 'Missing headers: '.implode(', ', $missingHeaders)]);
            }

            // Pre-fetch all QA codes once (for existence checks)
            $allQaCodes = Inventory::pluck('rfid_code')->toArray();

            $importData = [];
            $sl_no = 1; // Initialize once before processing all rows
            foreach ($formattedData['body'] as $rowIndex => $row) {
                if (empty($row) || (count(array_filter($row)) === 0)) {
                    continue; // skip empty rows
                }

                $rowNumber = $rowIndex + 1; // keep same as before

                // Read columns (original header keys)
                $productName = trim($row['Product Name'] ?? '');
                $sku = trim($row['SKU'] ?? '') ?: null; // optional
                $size = trim($row['Size'] ?? '');
                $referenceCode = isset($row['Reference Code']) ? trim($row['Reference Code']) : null;
                $qc_confirmed_at_raw = isset($row['QC Confirmed At']) ? trim($row['QC Confirmed At']) : null;
                $qtyRaw = $row['QTY'] ?? null;
                $sheetQaCode = $row['QA Code'] ?? null; // used for update_existing

                // Basic required checks
                if (($actionType != 'update_existing') && ($productName === '' || $size === '')) {
                    return response()->json(['success' => false, 'message' => "Row {$rowNumber}: Missing required fields. Product Name and Size are mandatory."]);
                }

                // QTY validation & normalization (default 1)
                $qty = (int) $qtyRaw;
                if ($qty < 1) {
                    $qty = 1;
                }

                // For update_existing: QA Code is required in sheet and must exist
                if ($actionType === 'update_existing') {
                    if (! $sheetQaCode) {
                        return response()->json(['success' => false, 'message' => "Row {$rowNumber}: QA Code is required Header."]);
                    }
                    if (! in_array($sheetQaCode, $allQaCodes)) {
                        return response()->json(['success' => false, 'message' => "Row {$rowNumber}: QA Code '{$sheetQaCode}' does not exist for update."]);
                    }
                }

                // QC Confirmed At validation (FileImportHelper may already normalized Excel dates to Y-m-d H:i:s)
                $qc_confirmed_at = null;

                if (! empty($qc_confirmed_at_raw)) {
                    try {
                        // Normalize separators to "/"
                        $normalized = str_replace('-', '/', $qc_confirmed_at_raw);

                        // Try parsing with time first
                        try {
                            $parsed = \Carbon\Carbon::createFromFormat('d/m/Y H:i:s', $normalized);
                        } catch (\Exception $e1) {
                            // If no time, fallback to date-only
                            try {
                                $parsed = \Carbon\Carbon::createFromFormat('d/m/Y', $normalized)->startOfDay();
                            } catch (\Exception $e2) {
                                // If still fails, try parsing with Carbon auto parser (for Y-m-d etc.)
                                $parsed = \Carbon\Carbon::parse($qc_confirmed_at_raw);
                            }
                        }

                        $qc_confirmed_at = $parsed->format('Y-m-d H:i:s');

                    } catch (\Exception $e) {
                        return response()->json([
                            'success' => false,
                            'message' => "Row {$rowNumber}: Invalid QC Confirmed At format. Expected 'DD-MM-YYYY', 'DD-MM-YYYY HH:mm:ss', 'DD/MM/YYYY' or 'DD/MM/YYYY HH:mm:ss'.",
                        ]);
                    }
                }

                // Branch: upload_new
                if ($actionType === 'upload_new') {
                    // Generate QA code and date/month/year for new uploads
                    $modelCode = $this->getModelFromProductName($productName);
                    $date = (int) date('d');
                    $month = (int) date('m');
                    $year = (int) date('Y'); // 4-digit year
                    $qa_code = "{$modelCode}-{$size}-{$date}{$month}{$year}";

                    // Generate multiple rows for the given QTY

                    for ($i = 0; $i < $qty; $i++) {

                        $importData[] = [
                            'product_name' => $productName,
                            'sku' => $sku,
                            'reference_code' => $referenceCode,
                            'qc_confirmed_at' => $qc_confirmed_at,
                            'size' => $size,
                            'model' => $modelCode,
                            'rfid_code' => $qa_code,
                            'date' => $date,
                            'month' => $month,
                            'year' => $year,
                            'serial_no' => $sl_no ?? null,
                            'bonding_name' => $row['Bonding Name'] ?? null,
                            'quantity' => 1,
                            'created_at' => now(),
                            'updated_at' => now(),
                            'location_id' => $location_id ? $location_id : LocaleHelper::getLoginUserLocationId(),
                        ];
                        $sl_no++;
                    }
                } else {
                    // Branch: update_existing
                    // Prevent updating records that are already written (is_write = 1)
                    $writtenQa = Inventory::where('rfid_code', $sheetQaCode)
                        ->where('is_write', 1)
                        ->pluck('rfid_code')
                        ->toArray();

                    if (in_array($sheetQaCode, $writtenQa)) {
                        return response()->json(['success' => false, 'message' => "Row {$rowNumber}: Written QA Code '{$sheetQaCode}' cannot be updated."]);
                    }

                    // Fetch existing records for this QA Code
                    $existingRecords = Inventory::where('rfid_code', $sheetQaCode)->get();

                    foreach ($existingRecords as $info) {
                        // Build update data but keep existing values as fallback.
                        $updateData = [
                            'sku' => $sku !== null ? $sku : $info->sku ?? null,
                            'reference_code' => $referenceCode !== null ? $referenceCode : $info->reference_code ?? null,
                            'qc_confirmed_at' => $qc_confirmed_at !== null ? $qc_confirmed_at : $info->qc_confirmed_at ?? null,
                            // 'size' => $size !== '' ? $size : $info->size,
                            // 'model' => ! empty($this->getModelFromProductName($productName)) ? $this->getModelFromProductName($productName) : $info->model,
                            // Do NOT overwrite qa_code/date/month/year unless the sheet explicitly includes and you want that behavior.
                            // 'date' => $info->date,
                            // 'month' => $info->month,
                            // 'year' => $info->year,
                            // 'serial_no' => isset($row['Serial No']) ? $row['Serial No'] : $info->serial_no,
                        ];

                        if (array_key_exists('Bonding Name', $row)) {
                            $updateData['bonding_name'] = $row['Bonding Name'] ?? $info->bonding_name;
                        }

                        $importData[] = [
                            '__update_qa_code' => $sheetQaCode,
                            'data' => $updateData,
                        ];
                    }
                }
            }

            // Save to DB
            // print_r($importData);
            // exit;

            if (! empty($importData)) {
                if ($actionType === 'upload_new') {
                    // Insert new rows in DB
                    Inventory::insert($importData);
                } else {

                    // Process updates
                    // foreach ($importData as $item) {
                    //     if (isset($item['__update_qa_code'])) {
                    //         Inventory::where('rfid_code', $item['__update_qa_code'])->update($item['data']);
                    //     }
                    // }

                    foreach ($importData as $item) {
                        if (isset($item['__update_qa_code'])) {
                            $qaCode = $item['__update_qa_code'];
                            $locationId = $location_id ? $location_id : LocaleHelper::getLoginUserLocationId();

                            // Update only for the given location
                            Inventory::where('rfid_code', $qaCode)
                                ->where('location_id', $locationId)
                                ->update($item['data']);
                        }
                    }

                }
            }

            DB::commit();

            return response()->json(['success' => true, 'message' => 'Bonding plan product data processed successfully.']);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json(['success' => false, 'message' => 'Upload failed: '.$e->getMessage()]);
        }
    }

    public function getModelFromProductName($productName)
    {

        // echo getModelFromProductName("Lux pro");        // LUP
        // echo getModelFromProductName("Lux pro Demon"); // LPD
        // echo getModelFromProductName("Lux");           // LUX
        // echo getModelFromProductName("Luxuman");       // LUX

        // split and keep only alphabetic characters in each word
        $rawWords = preg_split('/\s+/', trim($productName));
        $words = [];
        foreach ($rawWords as $w) {
            $clean = preg_replace('/[^A-Za-z]/', '', $w);
            if ($clean !== '') {
                $words[] = $clean;
            }
        }

        if (empty($words)) {
            return 'N/A';
        }

        $count = count($words);
        $model = '';

        if ($count === 1) {
            // single word -> first 3 letters
            $model = mb_strtoupper(mb_substr($words[0], 0, 3));
        } elseif ($count === 2) {
            $first = $words[0];
            $second = $words[1];
            if (mb_strlen($first) >= 2) {
                $model = mb_strtoupper(mb_substr($first, 0, 2).mb_substr($second, 0, 1));
            } else {
                // first word only 1 char -> take 1 from first + 2 from second
                $model = mb_strtoupper(mb_substr($first, 0, 1).mb_substr($second, 0, 2));
            }
        } else {
            // 3 or more words -> first letter of each of first 3 words
            for ($i = 0; $i < 3; $i++) {
                $model .= mb_strtoupper(mb_substr($words[$i], 0, 1));
            }
        }

        // ensure exactly 3 chars (pad with 'X' if very short)
        if (mb_strlen($model) < 3) {
            $model = str_pad($model, 3, 'X');
        } elseif (mb_strlen($model) > 3) {
            $model = mb_substr($model, 0, 3);
        }

        return $model;
    }
}
