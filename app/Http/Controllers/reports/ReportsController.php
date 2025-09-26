<?php

namespace App\Http\Controllers\reports;

use App\Exports\ProductExport;
use App\Helpers\LocaleHelper;
use App\Helpers\TableHelper;
use App\Helpers\UtilityHelper;
use App\Http\Controllers\Controller;
use App\Models\products\ProductProcessHistory;
use App\Models\products\Products;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;

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
            'total_qa_code' => $productsOverviewRaw['total_qa_code'],
            'total_pass_products' => $productsOverviewRaw['total_passed'],
            'total_fail_products' => $productsOverviewRaw['total_failed'],
        ];

        $pageConfigs = ['pageHeader' => true, 'isFabButton' => true];
        $currentUrl = $request->url();
        $UtilityHelper = new UtilityHelper;
        $createPermissions = $UtilityHelper::CheckModulePermissions('products', 'create.products');
        $table_headers = TableHelper::get_manage_table_headers($headers, true, true, true);

        $configData = UtilityHelper::getProductStagesAndDefectPoints();

        return view('content.reports.list')
            ->with('pageConfigs', $pageConfigs)
            ->with('table_headers', $table_headers)
            ->with('currentUrl', $currentUrl)
            ->with('productsOverview', $productsOverview)
            ->with('createPermissions', $createPermissions)
            ->with('stages', $configData['stages'] ?? [])
            ->with('defect_points', $configData['defect_points'] ?? [])
            ->with('status', $configData['status'] ?? []);
    }

    public function commonHeader()
    {
        return [
            ['created_at' => 'Created Date'],
            ['product_name' => 'Product Name'],
            ['sku' => 'SKU'],
            ['size' => 'Size'],
            ['qa_code' => 'Qa Code'],
            ['quantity' => 'Quantity'],
            ['status' => 'QC Status'],
            ['stage' => 'Current Stage'],
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

        $history = ProductProcessHistory::where('product_id', $row->id)
            ->latest('changed_at')
            ->first();
        // -------------- QC Status ---------------
        $statusHTML = '';
        switch ($history->status ?? $row->status) {
            case 'PASS':
                $statusHTML = '<span class="badge rounded bg-label-success " title="PASS"><i class="icon-base bx bx-check-circle icon-lg me-1"></i>PASS</span>';
                break;
            case 'FAIL':
                $statusHTML = '<span class="badge rounded bg-label-danger " title="FAIL"><i class="icon-base bx bx-check-circle icon-lg me-1"></i>FAIL</span>';
                break;
            default:
                $statusHTML = '<span class="badge rounded bg-label-primary" title="PENDING"><i class="icon-base bx bx-check-circle icon-lg me-1"></i>PENDING</span>';
                break;
        }
        // -------------- QC Status ---------------
        // -------------- product stage ---------------
        $getStageName = LocaleHelper::getStageName($history->stages);
        $current_stage = $getStageName ?? 'Bonding';

        $stageHTML = '<span class="badge rounded bg-label-warning " title="Active"><i class="icon-base bx bx-message-alt-detail me-1"></i>'.$current_stage.'</span>';
        // -------------- product stages ---------------
        $data['created_at'] = LocaleHelper::formatDateWithTime($row->created_at);
        $data['product_name'] = $row->product_name;
        $data['sku'] = $row->sku;
        $data['size'] = $row->size;
        $data['qa_code'] = $row->qa_code;
        $data['quantity'] = $row->quantity;
        $data['status'] = $statusHTML;
        $data['stage'] = $stageHTML;

        return $data;
    }

    /**
     * Fetch report data based on report_type with support for filters and dynamic headers
     */
    public function list(Request $request)
    {
        $reportType = $request->input('report_type', 'default_report');
        $search = $request->get('search') ?? '';
        $limit = 100;
        $offset = 0;
        $sort = $request->get('sort') ?? 'created_at';
        $order = $request->get('order') ?? 'desc';

        $filters = [
            'status' => $request->get('status') ?? '',
            'stages' => $request->get('stage') ?? '',
        ];
        $selectedDate = $request->get('selectedDaterange') ?? $request->get('default_dateRange');
        $daterange = LocaleHelper::dateRangeDateInputFormat($selectedDate);
        if ($daterange) {
            $filters['start_date'] = $daterange['start_date'] ?? '';
            $filters['end_date'] = $daterange['end_date'] ?? '';
        }

        // print_r($request->all());
        // exit;

        $data_rows = [];
        $columns = [];

        $headers = [
            ['created_at' => 'Created Date'],
            ['product_name' => 'Product Name'],
            ['sku' => 'SKU'],
            ['size' => 'Size'],
            ['qa_code' => 'QA Code'],
            ['quantity' => 'Quantity'],
            ['status' => 'QC Status'],
            ['stage' => 'Current Stage'],
        ];
        foreach ($headers as $header) {
            foreach ($header as $data => $title) {
                $columns[] = ['data' => $data, 'title' => $title];
            }
        }

        switch ($reportType) {
            case 'daily_floor_stock_report':
                $searchData = $this->products->report_search($search, $filters, $limit, $offset, $sort, $order, $reportType);
                $total_rows = $this->products->get_found_rows($search);

                // $headers = [
                //     ['created_at' => 'Created Date'],
                //     ['product_name' => 'Product Name'],
                //     ['sku' => 'SKU'],
                //     ['size' => 'Size'],
                //     ['qa_code' => 'QA Code'],
                //     ['quantity' => 'Quantity'],
                //     ['status' => 'QC Status'],
                //     ['stage' => 'Current Stage'],
                // ];
                // foreach ($headers as $header) {
                //     foreach ($header as $data => $title) {
                //         $columns[] = ['data' => $data, 'title' => $title];
                //     }
                // }
                foreach ($searchData as $row) {
                    $data_rows[] = $this->tableHeaderRowData($row);
                }
                break;

            case 'daily_bonding_report':
                $filters['stages'] = 'bonding_qc';
                $searchData = $this->products->getStockReport($search, $filters, $limit, $offset, $sort, $order);
                $total_rows = $this->products->getStockReportCount($search, $filters);

                // $headers = [
                //     ['product_name' => 'Product Name'],
                //     ['sku' => 'SKU'],
                //     ['quantity' => 'Quantity'],
                //     ['status' => 'QC Status'],
                //     ['stage' => 'Current Stage'],
                // ];
                // foreach ($headers as $header) {
                //     foreach ($header as $data => $title) {
                //         $columns[] = ['data' => $data, 'title' => $title];
                //     }
                // }
                // foreach ($searchData as $row) {
                //     $history = ProductProcessHistory::where('product_id', $row->id)->first();
                //     $data_rows[] = [
                //         'product_name' => $row->product_name,
                //         'sku' => $row->sku,
                //         'quantity' => $row->quantity,
                //         'status' => $history ? $history->status : '',
                //         'stage' => $history ? LocaleHelper::getStageName($history->stages) : '',
                //     ];
                // }
                // break;
                foreach ($searchData as $row) {
                    $data_rows[] = $this->tableHeaderRowData($row);
                }
                break;
            case 'monthly_yearly_report':
                $filters['start_date'] = '';
                $filters['end_date'] = '';
                $searchData = $this->products->getStockReport($search, $filters, $limit, $offset, $sort, $order);
                $total_rows = $this->products->getStockReportCount($search, $filters);
                foreach ($searchData as $row) {
                    $data_rows[] = $this->tableHeaderRowData($row);
                }
                break;
            case 'daily_packing_report':
                $filters['stages'] = 'packaging';
                $searchData = $this->products->getStockReport($search, $filters, $limit, $offset, $sort, $order);
                $total_rows = $this->products->getStockReportCount($search, $filters);
                foreach ($searchData as $row) {
                    $data_rows[] = $this->tableHeaderRowData($row);
                }
                break;
            case 'daily_tapedge_report':
                $filters['stages'] = 'tape_edge_qc';
                $searchData = $this->products->getStockReport($search, $filters, $limit, $offset, $sort, $order);
                $total_rows = $this->products->getStockReportCount($search, $filters);
                foreach ($searchData as $row) {
                    $data_rows[] = $this->tableHeaderRowData($row);
                }
                break;

            case 'daily_zip_cover_report':
                $filters['stages'] = 'zip_cover_qc';
                $searchData = $this->products->getStockReport($search, $filters, $limit, $offset, $sort, $order);
                $total_rows = $this->products->getStockReportCount($search, $filters);
                foreach ($searchData as $row) {
                    $data_rows[] = $this->tableHeaderRowData($row);
                }
                break;

            default:
                $searchData = $this->products->report_search($search, $filters, $limit, $offset, $sort, $order, $reportType);
                $total_rows = $this->products->get_found_rows($search);
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

        if (! empty($filters['start_date']) && ! empty($filters['end_date'])) {
            $query->whereBetween('created_at', [$filters['start_date'], $filters['end_date']]);
        }

        if (! empty($filters['status']) && $filters['status'] !== 'all') {
            $query->where('status', $filters['status']);
        }

        $rows = $query->get();

        return $rows->map(function ($item) {
            $history = ProductProcessHistory::where('product_id', $item->id)->first();

            return [
                'product_name' => $item->product_name,
                'sku' => $item->sku,
                'quantity' => $item->quantity,
                'status' => $history ? $history->status : $item->status,
                'current_stage' => $history ? $history->stages : '',
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
                $product->qa_code,
                $product->quantity,
                $product->status,
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

        return Excel::download(new ProductExport($dataRows, $metaInfo, $headers), 'products_export_'.now()->format('Ymd_His').'.xlsx');
    }
}
