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
use Maatwebsite\Excel\Concerns\FromArray;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Models\user_management\UsersModel;

use Illuminate\Support\Facades\Response;

use App\Exports\ProductExport;
use App\Models\products\Products;

class ProductsController extends Controller
{
    protected $products;
    public function __construct()
    {
        $this->products = new Products;
    }
    public function index(Request $request)
    {
        $headers = [
            array('created_at' => 'Created Date'),
            array('product_name' => 'Product Name'),
            array('sku' => 'SKU'),
            array('size' => 'Size'),
            array('rfid_tag' => 'RFID Tag'),
            array('quantity' => 'Quantity'),
            array('qc_status' => 'QC Status'),
            array('current_stage' => 'Current Stage'),
            array('actions' => 'Actions')
        ];

        // Get the counts
        $productsOverview = LocaleHelper::getProductSummaryCounts();

        // Map keys to match what your view expects
        $productsOverview = [
            'total_products' => $productsOverview['total_products'],
            'total_tags' => $productsOverview['total_rfid_tags'],
            'total_pass_products' => $productsOverview['total_pass'],
            'total_failed_products' => $productsOverview['total_failed'],
        ];

        $pageConfigs = ['pageHeader' => true, 'isFabButton' => true];
        $currentUrl = $request->url();
        $UtilityHelper = new UtilityHelper();
        $createPermissions = $UtilityHelper::CheckModulePermissions('products', 'create.products');
        $table_headers = TableHelper::get_manage_table_headers($headers, true, true, true);




        // /print_r($table_headers); exit;
        return view('content.products.list')
            ->with('pageConfigs', $pageConfigs)
            ->with('table_headers', $table_headers)
            ->with('currentUrl', $currentUrl)
            ->with('productsOverview', $productsOverview)
            ->with('createPermissions', $createPermissions);
    }
    protected function tableHeaderRowData($row)
    {
        $data = [];
        $view = route('view.products', ["code" => $row->id]);
        $edit = route('edit.products', ["id" => $row->id]);
        $delete = route('delete.products', ["id" => $row->id]);
        // -------------- QC Status ---------------
        $statusHTML = '';
        switch ($row->qc_status) {
            case 'PASS':
                $statusHTML = '<span class="badge rounded bg-label-success " title="Active"><i class="icon-base bx bx-check-circle icon-lg me-1"></i>PASS</span>';
                break;
            case 'FAILED':
                $statusHTML = '<span class="badge rounded bg-label-danger " title="Active"><i class="icon-base bx bx-check-circle icon-lg me-1"></i>FAILED</span>';
                break;
            default:
                $statusHTML = '<span class="badge rounded bg-label-primary" title="PENDING"><i class="icon-base bx bx-check-circle icon-lg me-1"></i>PENDING</span>';
                break;
        }
        // -------------- QC Status ---------------
        // -------------- product stage ---------------
        $stageHTML = '<span class="badge rounded bg-label-success " title="Active"><i class="icon-base bx bx-message-alt-detail me-1"></i>' . $row->current_stage . '</span>';
        // -------------- product stages ---------------
        $data['created_at'] =  LocaleHelper::formatDateWithTime($row->created_at);
        $data['product_name'] =  $row->product_name;
        $data['sku'] =  $row->sku;
        $data['size'] =  $row->size;
        $data['rfid_tag'] =  $row->rfid_tag;
        $data['quantity'] =  $row->quantity;
        $data['qc_status'] =  $statusHTML;
        $data['current_stage'] =  $stageHTML;

        // ===============    Common action dropdown display add/edit/view/delete  ============= //
        $data['actions'] = '<div class="d-inline-block">
    	<a href="javascript:;" class="btn btn-sm text-primary btn-icon dropdown-toggle hide-arrow" data-bs-toggle="dropdown"><i class="bx bx-dots-vertical-rounded"></i></a>
    	<ul class="dropdown-menu dropdown-menu-end">
    	<li><a href="javascript:;" class="dropdown-item text-primary"><i class="bx bx-file me-1"></i>View Details</a></li>
        <li><a href="' . $edit . '" class="dropdown-item text-primary item-edit"><i class="bx bxs-edit me-1"></i>Edit</a></li>
        <li><a href="javascript:;" onclick="deleteRow(\'' . $delete . '\');" class="dropdown-item text-danger delete-record"><i class="bx bx-trash me-1"></i>Delete</a></li>
    	<div class="dropdown-divider"></div>
    	</ul>
    	</div>
    	';
        return $data;
    }


    /*Returns quotations table data rows. This will be called with AJAX.*/
    public function list(Request $request)
    {
        $search =  $request->get('search') ?? '';
        $limit  =  100;
        $offset =  0;
        $sort = $request->get('sort') ?? 'created_at';
        $order = $request->get('order') ?? 'desc';
        $filters = [
            'current_stage' => $request->get('current_stage') ?? '',
            'qc_status' => $request->get('qc_status') ?? '',
        ];
        $seletedDate = $request->get('selectedDaterange') ?? $request->get('default_dateRange');
        $daterange = LocaleHelper::dateRangeDateInputFormat($seletedDate); // Date range input date format
        if ($daterange) {
            $filters['start_date'] = $daterange['start_date'] ?? '';
            $filters['end_date'] = $daterange['end_date'] ?? '';
        }
        $searchData = $this->products->search($search, $filters, $limit, $offset, $sort, $order);
        $total_rows = $this->products->get_found_rows($search);
        $is_edit = 1; /* check if permission is there to edit */
        // print_r($searchData);
        // exit;
        $data_rows = [];
        foreach ($searchData as $row) {
            $data_rows[] = $this->tableHeaderRowData($row);
        }
        $response = [
            'data' => $data_rows,
            'recordsTotal' => $total_rows,
            'recordsFiltered' => $total_rows,
        ];
        echo json_encode($response);
    }


    public function create(Request $request)
    {
        $data = [];
        return view('content.products.create', $data);
    }
    public function edit(Request $request, $id)
    {
        // Retrieve the product or fail with 404
        $product = Products::find($id);
        if (!$product) {
            return view('content.miscellaneous.no-data');
        }

        // Pass product data to the view
        return view('content.products.edit', ['product' => $product]);
    }

    public function save(Request $request, $id = null)
    {
        // Prepare validation rules
        $rules = [
            'product_name' => 'required|string|max:150',
            'sku' => 'required|string|max:100',
            'reference_code' => 'nullable|string|max:150',
            'size' => 'required|string|max:150',
            'rfid_tag' => 'required|string|max:200',
            'quantity' => 'nullable|integer|min:0',
        ];

        // Custom messages
        $messages = [
            'rfid_tag.unique' => 'The RFID Tag has already been taken.',
            'sku.unique' => 'The SKU has already been taken.',
        ];

        // Adjust unique validation for sku and rfid_tag on create or update
        if ($id) {
            // Update: exclude current record from unique check
            $rules['sku'] .= ",sku,$id";
            $rules['rfid_tag'] .= ",rfid_tag,$id";
        } else {
            // Create: ensure unique
            $rules['sku'] .= '|unique:products,sku';
            $rules['rfid_tag'] .= '|unique:products,rfid_tag';
        }

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

        if (!$id) {
            // New product: set default qc_status
            $data['qc_status'] = 'PENDING';
        }

        $user = Auth::user();
        $data['qc_status_update_by'] = $user->id ?? null;

        DB::beginTransaction();

        try {
            $action = '';
            if ($id) {
                $product = Products::findOrFail($id);
                $product->update($data);
                $action = 'update';
            } else {
                $product = Products::create($data);
                $action = 'create';
            }

            // Log activity accordingly
            $this->UserActivityLog(
                $request,
                [
                    'module' => 'products',
                    'activity_type' => $action,
                    'message' => ucfirst($action) . "d product: " . $product->product_name,
                    'application' => 'web',
                    'data' => $data,
                ]
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Product successfully " . ($action === 'create' ? "created" : "updated") . ".",
                'return_url' => route('products'),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Product ' . $action . ' error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'An error occurred: ' . $e->getMessage(),
            ], 500);
        }
    }




    /* ------------------  Delete selected Items ----------------------- */
    public function delete(Request $request, $id = '')
    {
        $delete_id = ($id) ? $id : $request->input('id');
        $Model = Products::find($delete_id);
        if (!$Model) {
            return response()->json(['success' => false, 'message' => 'Delete  Failed!', 'bg_color' => 'bg-danger']);
        }
        try {
            $Model->delete();
            // Insert user activity -------------------- START ---------------------
            $user = Auth::user();
            $this->UserActivityLog(
                $request,
                [
                    'module' => 'products',
                    'activity_type' => 'delete',
                    'message' => 'Delete products by : ' . $user->fullname,
                    'application' => 'web',
                    'data' => ['sku' => $Model->sku, 'rfid_tag' => $Model->rfid_tag]
                ]
            );
            // Insert user activity -------------------- END ------------------------
            return response()->json(['success' => TRUE, 'message' => 'Record deleted successfully', 'bg_color' => 'bg-success']);
        } catch (\Exception $e) {
            return response()->json(['success' => FALSE, 'message' => 'Delete  Failed!' . ' ' . $e->getMessage()]);
        }
    }



    //------------------------ Download customer import format ---------------------------
    public function productImportFormat(Request $request)
    {
        $header = [];
        $data = [];
        try {
            //Set Headers
            $rowData = [];
            $header = [
                'SKU',
                'Product Name',
                'Size',
                'Quantity',
                'RFID Tag',
                'Reference Code',
            ];
            $data[] = $header;
            $data[] = $rowData;

            return Excel::download(new class($data) implements \Maatwebsite\Excel\Concerns\FromArray {
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
            return back()->with('error', 'There was an error generating the report: ' . $e->getMessage());
        }
    }
    public function bulkProductUpload(Request $request)
    {
        // Validate file input and action_type field
        $validator = Validator::make($request->all(), [
            'file' => 'required|mimes:xlsx,xls|mimetypes:application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-excel',
            'action_type' => 'required|in:upload_new,update_existing',
        ]);

        if ($validator->fails()) {
            Log::error("Product bulk upload failed: " . $validator->errors());
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
                'message' => 'Invalid format for upload file or action type.'
            ]);
        }

        $actionType = $request->input('action_type');
        $file = $request->file('file');

        DB::beginTransaction();

        try {
            $formattedData = FileImportHelper::getFileData($file);

            if (!$formattedData || !isset($formattedData['header']) || !isset($formattedData['body']) || count($formattedData['body']) < 1) {
                return response()->json(['success' => false, 'message' => 'No product data found to upload.']);
            }

            // Validate mandatory headers exist
            $actualHeaders = $formattedData['header'];
            $missingHeaders = array_diff(['Product Name', 'SKU', 'Size'], $actualHeaders);
            if (count($missingHeaders) > 0) {
                return response()->json(['success' => false, 'message' => 'Missing mandatory header(s): ' . implode(', ', $missingHeaders)]);
            }

            $importData = [];
            $chunkSize = 1000;
            $dataChunks = array_chunk($formattedData['body'], $chunkSize);

            // Load existing SKUs and RFID tags from DB
            $existingSkus = Products::pluck('sku')->toArray();
            $existingRfids = Products::pluck('rfid_tag')->toArray();

            // Track SKUs and RFID tags in current upload file
            $fileSkus = [];
            $fileRfids = [];

            foreach ($dataChunks as $chunk) {
                foreach ($chunk as $rowIndex => $row) {
                    $productName = trim($row['Product Name']);
                    $sku = trim($row['SKU']);
                    $size = trim($row['Size']);
                    $rfidTag = trim($row['RFID Tag'] ?? '');
                    $referenceCode = $row['Reference Code'] ?? null;
                    $quantity = isset($row['Quantity']) ? (int)$row['Quantity'] : 0;

                    // Validate mandatory fields
                    if (empty($productName) || empty($sku) || empty($size)) {
                        return response()->json([
                            'success' => false,
                            'message' => "Missing required fields at row " . ($rowIndex + 1) . ". Product Name, SKU, and Size are mandatory."
                        ]);
                    }

                    if ($actionType === 'upload_new') {
                        // 1️⃣ Check against DB
                        if (in_array($sku, $existingSkus)) {
                            return response()->json([
                                'success' => false,
                                'message' => "SKU '{$sku}' at row " . ($rowIndex + 1) . " already exists in database."
                            ]);
                        }
                        if ($rfidTag !== '' && in_array($rfidTag, $existingRfids)) {
                            return response()->json([
                                'success' => false,
                                'message' => "RFID Tag '{$rfidTag}' at row " . ($rowIndex + 1) . " already exists in database."
                            ]);
                        }

                        // 2️⃣ Check duplicates in the same file
                        if (in_array($sku, $fileSkus)) {
                            return response()->json([
                                'success' => false,
                                'message' => "SKU '{$sku}' at row " . ($rowIndex + 1) . " is duplicated in the upload file."
                            ]);
                        }
                        if ($rfidTag !== '' && in_array($rfidTag, $fileRfids)) {
                            return response()->json([
                                'success' => false,
                                'message' => "RFID Tag '{$rfidTag}' at row " . ($rowIndex + 1) . " is duplicated in the upload file."
                            ]);
                        }

                        // Track file SKUs and RFID tags
                        $fileSkus[] = $sku;
                        if ($rfidTag !== '') {
                            $fileRfids[] = $rfidTag;
                        }
                    } elseif ($actionType === 'update_existing') {
                        // For update, SKU must exist
                        if (!Products::where('sku', $sku)->exists()) {
                            return response()->json([
                                'success' => false,
                                'message' => "SKU '{$sku}' at row " . ($rowIndex + 1) . " does not exist for update."
                            ]);
                        }
                    }

                    $productData = [
                        'product_name' => $productName,
                        'sku' => $sku,
                        'reference_code' => $referenceCode,
                        'size' => $size,
                        'rfid_tag' => $rfidTag ?: null,
                        'quantity' => $quantity,
                        'qc_status' => 'PENDING',
                        'qc_confirmed_at' => null,
                        'qc_status_update_by' => null,
                        'updated_at' => now(),
                    ];

                    // Add created_at only for new uploads
                    if ($actionType === 'upload_new') {
                        $productData['created_at'] = now();
                    }

                    $importData[] = $productData;
                }

                if (!empty($importData)) {
                    if ($actionType === 'update_existing') {
                        // Update products one by one based on SKU
                        foreach ($importData as $productData) {
                            unset($productData['created_at']); // Prevent overwriting
                            Products::where('sku', $productData['sku'])->update($productData);
                        }
                    } elseif ($actionType === 'upload_new') {
                        // Bulk insert new products
                        Products::insert($importData);
                    }

                    // Log user activity for bulk import
                    $this->UserActivityLog(
                        $request,
                        [
                            'module' => 'products',
                            'activity_type' => $actionType === 'upload_new' ? 'bulk_upload_new' : 'bulk_update_existing',
                            'message' => $actionType === 'upload_new'
                                ? 'Bulk upload of new products completed'
                                : 'Bulk update of existing products completed',
                            'application' => 'web',
                            'data' => $importData,
                        ]
                    );

                    // Clear importData for next chunk
                    $importData = [];
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Product data processed successfully.'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error("Product bulk upload error: " . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Bulk upload failed: ' . $e->getMessage(),
            ]);
        }
    }




    public function exportProducts(Request $request)
    {
        $user = Auth::user();
        $daterange = $request->input('productsDaterangePicker');

        // Prepare date info or other meta info
        $metaInfo = [
            'date_range' => $daterange ?: 'All time',
            'generated_by' => $user->fullname ?? 'System',
        ];

        // Retrieve your products data rows as arrays matching headers order
        $products = Products::all();

        $dataRows = $products->map(function ($product) {
            return [
                $product->product_name,
                $product->sku,
                $product->reference_code,
                $product->size,
                $product->rfid_tag,
                $product->quantity,
                $product->qc_status,
                $product->qc_confirmed_at ? $product->qc_confirmed_at->format('Y-m-d H:i:s') : '',
                $product->created_at->format('Y-m-d H:i:s'),
                $product->updated_at->format('Y-m-d H:i:s'),
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
            'QC Confirmed At',
            'Created At',
            'Updated At',
        ];

        return Excel::download(new ProductExport($dataRows, $metaInfo, $headers), 'products_export_' . now()->format('Ymd_His') . '.xlsx');
    }


    //------------------------ Download customer MIS import format ---------------------------
}