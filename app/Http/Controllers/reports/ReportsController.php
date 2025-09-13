<?php

namespace App\Http\Controllers\reports;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Helpers\TableHelper;
use App\Helpers\LocaleHelper;
use App\Helpers\UtilityHelper;
use Illuminate\Support\Facades\Auth;
use App\Models\products\Products;
use App\Models\products\ProductProcessHistory;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ProductExport;

class ReportsController extends Controller
{
    protected $products;

    public function __construct()
    {
        $this->products = new Products;
    }

    public function index(Request $request)
    {
        $headers = $this->commonHeader();

        // Get the counts overview
        $productsOverviewRaw = LocaleHelper::getProductSummaryCounts();
        $productsOverview = [
            'total_products' => $productsOverviewRaw['total_products'],
            'total_tags' => $productsOverviewRaw['total_rfid_tags'],
            'total_pass_products' => $productsOverviewRaw['total_pass'],
            'total_failed_products' => $productsOverviewRaw['total_failed'],
        ];

        $pageConfigs = ['pageHeader' => true, 'isFabButton' => true];
        $currentUrl = $request->url();
        $UtilityHelper = new UtilityHelper();
        $createPermissions = $UtilityHelper::CheckModulePermissions('products', 'create.products');
        $table_headers = TableHelper::get_manage_table_headers($headers, true, true, true);

        return view('content.reports.list')
            ->with('pageConfigs', $pageConfigs)
            ->with('table_headers', $table_headers)
            ->with('currentUrl', $currentUrl)
            ->with('productsOverview', $productsOverview)
            ->with('createPermissions', $createPermissions);
    }

    public function commonHeader()
    {
        return  [
            ['created_at' => 'Created Date'],
            ['product_name' => 'Product Name'],
            ['sku' => 'SKU'],
            ['size' => 'Size'],
            ['rfid_tag' => 'RFID Tag'],
            ['quantity' => 'Quantity'],
            ['qc_status' => 'QC Status'],
            ['current_stage' => 'Current Stage'],
            ['actions' => 'Actions']
        ];
        //     $headers = [
        //     ['bonding_date' => 'Bonding Date'],
        //     ['mattress_model' => 'Mattress Model/Type'],
        //     ['batch_number' => 'Batch Number'],
        //     ['operator_name' => 'Operator Name'],
        //     ['machine_id' => 'Bonding Machine ID'],
        //     ['adhesive_type' => 'Adhesive Type'],
        //     ['quantity_bonded' => 'Quantity Bonded'],
        //     ['bonding_start_time' => 'Bonding Start Time'],
        //     ['bonding_end_time' => 'Bonding End Time'],
        //     ['inspection_status' => 'Inspection Status'],
        //     ['defect_description' => 'Defect Description'],
        //     ['rework_required' => 'Rework Required'],
        //     ['notes' => 'Notes'],
        // ];

    }

    protected function tableHeaderRowData($row)
    {
        $data = [];
        $view = route('view.products', ["code" => $row->id]);
        $edit = route('edit.products', ["id" => $row->id]);
        $delete = route('delete.products', ["id" => $row->id]);

 $history = ProductProcessHistory::where('product_id', $row->id)->first();
        // -------------- QC Status ---------------
        $statusHTML = '';
        switch ($history->status ?? $row->qc_status) {
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
        $current_stage=$history->stage??$row->current_stage;
        $stageHTML = '<span class="badge rounded bg-label-success " title="Active"><i class="icon-base bx bx-message-alt-detail me-1"></i>' . $current_stage . '</span>';
        // -------------- product stages ---------------
        $data['created_at'] = LocaleHelper::formatDateWithTime($row->created_at);
        $data['product_name'] = $row->product_name;
        $data['sku'] = $row->sku;
        $data['size'] = $row->size;
        $data['rfid_tag'] = $row->rfid_tag;
        $data['quantity'] = $row->quantity;
        $data['qc_status'] = $statusHTML;
        $data['current_stage'] =  $stageHTML;

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

    /**
     * Fetch report data based on report_type with support for filters and dynamic headers
     */
    public function list(Request $request)
    {
        $reportType = $request->input('report_type', 'default_report');
        $search = $request->get('search') ?? '';
        $limit  = 100;
        $offset = 0;
        $sort = $request->get('sort') ?? 'created_at';
        $order = $request->get('order') ?? 'desc';

        $filters = [
            'status' => $request->get('status') ?? '',
        ];
        $selectedDate = $request->get('selectedDaterange') ?? $request->get('default_dateRange');
        $daterange = LocaleHelper::dateRangeDateInputFormat($selectedDate);
        if ($daterange) {
            $filters['start_date'] = $daterange['start_date'] ?? '';
            $filters['end_date'] = $daterange['end_date'] ?? '';
        }

        $data_rows = [];
        $columns = [];

        switch ($reportType) {
            case 'stock_report':
                $searchData = $this->products->search($search, $filters, $limit, $offset, $sort, $order);
                $total_rows = $this->products->get_found_rows($search);

                $headers = [
                    ['created_at' => 'Created Date'],
                    ['product_name' => 'Product Name'],
                    ['sku' => 'SKU'],
                    ['size' => 'Size'],
                    ['rfid_tag' => 'RFID Tag'],
                    ['quantity' => 'Quantity'],
                    ['qc_status' => 'QC Status'],
                    ['current_stage' => 'Current Stage'],
                    ['actions' => 'Actions']
                ];
                foreach ($headers as $header) {
                    foreach ($header as $data => $title) {
                        $columns[] = ['data' => $data, 'title' => $title];
                    }
                }
                foreach ($searchData as $row) {
                    $data_rows[] = $this->tableHeaderRowData($row);
                }
                break;

            case 'daily_bonding_report':
                $searchData = $this->products->getStockReport($search, $filters, $limit, $offset, $sort, $order);
                $total_rows = $this->products->getStockReportCount($search, $filters);

                $headers = [
                    ['product_name' => 'Product Name'],
                    ['sku' =>  'SKU'],
                    ['quantity' => 'Quantity'],
                    ['qc_status' => 'QC Status'],
                    ['current_stage' => 'Current Stage'],
                ];
                foreach ($headers as $header) {
                    foreach ($header as $data => $title) {
                        $columns[] = ['data' => $data, 'title' => $title];
                    }
                }
                foreach ($searchData as $row) {
                    $history = ProductProcessHistory::where('product_id', $row->id)->first();
                    $data_rows[] = [
                        'product_name' => $row->product_name,
                        'sku' => $row->sku,
                        'quantity' => $row->quantity,
                        'qc_status' => $history ? $history->status : $row->qc_status,
                        'current_stage' => $history ? $history->stage : $row->current_stage,
                    ];
                }
                break;

            default:
                $searchData = $this->products->search($search, $filters, $limit, $offset, $sort, $order);
                $total_rows = $this->products->get_found_rows($search);

                $headers = [
                    ['created_at' => 'Created Date'],
                    ['product_name' => 'Product Name'],
                    ['sku' => 'SKU'],
                    ['size' => 'Size'],
                    ['rfid_tag' => 'RFID Tag'],
                    ['quantity' => 'Quantity'],
                    ['qc_status' => 'QC Status'],
                    ['current_stage' => 'Current Stage'],
                    ['actions' => 'Actions']
                ];
                foreach ($headers as $header) {
                    foreach ($header as $data => $title) {
                        $columns[] = ['data' => $data, 'title' => $title];
                    }
                }
                foreach ($searchData as $row) {
                    $data_rows[] = $this->tableHeaderRowData($row);
                }
                break;
        }

        return response()->json([
            'data' => $data_rows,
            'columns' => $columns,
            'recordsTotal' => $total_rows,
            'recordsFiltered' => $total_rows,
        ]);
    }

    /**
     * Example method in Products model to get stock report data
     */
    public function getStockReportData(array $filters)
    {
        $query = $this->products->newQuery();

        if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
            $query->whereBetween('created_at', [$filters['start_date'], $filters['end_date']]);
        }

        if (!empty($filters['status']) && $filters['status'] !== 'all') {
            $query->where('qc_status', $filters['status']);
        }

        $rows = $query->get();

        return $rows->map(function ($item) {
            $history = ProductProcessHistory::where('product_id', $item->id)->first();
            return [
                'product_name' => $item->product_name,
                'sku' => $item->sku,
                'quantity' => $item->quantity,
                'qc_status' => $history ? $history->status : $item->qc_status,
                'current_stage' => $history ? $history->stage : $item->current_stage,
            ];
        })->toArray();
    }

    /**
     * Exports products data to Excel
     */
    public function exportProducts(Request $request)
    {
        $user = Auth::user();
        $daterange = $request->input('productsDaterangePicker');

        $metaInfo = [
            'date_range' => $daterange ?: 'All time',
            'generated_by' => $user->fullname ?? 'System',
        ];

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
            'Current Stage',
            'QC Confirmed At',
            'Created At',
            'Updated At',
        ];

        return Excel::download(new ProductExport($dataRows, $metaInfo, $headers), 'products_export_' . now()->format('Ymd_His') . '.xlsx');
    }
}
