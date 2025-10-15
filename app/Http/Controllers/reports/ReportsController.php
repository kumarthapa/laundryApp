<?php

namespace App\Http\Controllers\reports;

use App\Exports\ProductExport;
use App\Helpers\LocaleHelper;
use App\Helpers\TableHelper;
use App\Helpers\UtilityHelper;
use App\Http\Controllers\Controller;
use App\Models\products\ProductProcessHistory;
use App\Models\products\Products;
use App\Models\reports\ReportsModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;

class ReportsController extends Controller
{
    protected $products;

    protected $reports;

    public function __construct()
    {
        $this->products = new Products;
        $this->reports = new ReportsModel;
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
    }

    protected function tableHeaderRowData($row)
    {
        $data = [];
        // print_r($row);
        // exit;
        // $history = ProductProcessHistory::where('product_id', $row->id)
        //     ->latest('changed_at')
        //     ->first();
        // -------------- QC Status ---------------
        $statusHTML = '';
        switch ($row->status) {
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
        $getStageName = LocaleHelper::getStageName($row->stages);
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
        // print_r($data);
        // exit;

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
                $searchData = $this->reports->daily_floor_stock_report_search($search, $filters, $limit, $offset, $sort, $order, $reportType);
                $total_rows = $this->reports->get_found_rows($search);

                foreach ($searchData as $row) {
                    $data_rows[] = $this->tableHeaderRowData($row);
                }
                break;

            case 'daily_bonding_report':
                $filters['stages'] = 'bonding_qc';
                $searchData = $this->reports->getCommonStockReport($search, $filters, $limit, $offset, $sort, $order, $reportType);
                $total_rows = $this->reports->getStockReportCount($search, $filters);

                foreach ($searchData as $row) {
                    $data_rows[] = $this->tableHeaderRowData($row);
                }
                break;
            case 'all_bonding_report':
                $filters['stages'] = 'bonding_qc';
                $filters['start_date'] = '';
                $filters['end_date'] = '';
                $searchData = $this->reports->getCommonStockReport($search, $filters, $limit, $offset, $sort, $order, $reportType);
                $total_rows = $this->reports->getStockReportCount($search, $filters);

                foreach ($searchData as $row) {
                    $data_rows[] = $this->tableHeaderRowData($row);
                }
                break;
            case 'floor_stock_bonding':
                $filters['stages'] = 'bonding_qc';
                $filters['status'] = 'PENDING';
                $filters['start_date'] = '';
                $filters['end_date'] = '';
                $searchData = $this->reports->getCommonStockReport($search, $filters, $limit, $offset, $sort, $order, $reportType);
                $total_rows = $this->reports->getStockReportCount($search, $filters);

                foreach ($searchData as $row) {
                    $data_rows[] = $this->tableHeaderRowData($row);
                }
                break;
            case 'monthly_yearly_report':
                $filters['start_date'] = '';
                $filters['end_date'] = '';
                $searchData = $this->reports->getCommonStockReport($search, $filters, $limit, $offset, $sort, $order, $reportType);
                $total_rows = $this->reports->getStockReportCount($search, $filters);
                foreach ($searchData as $row) {
                    $data_rows[] = $this->tableHeaderRowData($row);
                }
                break;
            case 'daily_packing_report':
                $filters['stages'] = 'packaging';
                $searchData = $this->reports->getCommonStockReport($search, $filters, $limit, $offset, $sort, $order, $reportType);
                $total_rows = $this->reports->getStockReportCount($search, $filters);
                foreach ($searchData as $row) {
                    $data_rows[] = $this->tableHeaderRowData($row);
                }
                break;
            case 'daily_tapedge_report':
                $filters['stages'] = 'tape_edge_qc';
                $searchData = $this->reports->getCommonStockReport($search, $filters, $limit, $offset, $sort, $order, $reportType);
                $total_rows = $this->reports->getStockReportCount($search, $filters);
                foreach ($searchData as $row) {
                    $data_rows[] = $this->tableHeaderRowData($row);
                }
                break;

            case 'daily_zip_cover_report':
                $filters['stages'] = 'zip_cover_qc';
                $searchData = $this->reports->getCommonStockReport($search, $filters, $limit, $offset, $sort, $order, $reportType);

                $total_rows = $this->reports->getStockReportCount($search, $filters);

                foreach ($searchData as $row) {
                    $data_rows[] = $this->tableHeaderRowData($row);
                }
                break;

            default:
                $searchData = $this->reports->daily_floor_stock_report_search($search, $filters, $limit, $offset, $sort, $order, $reportType);
                $total_rows = $this->reports->get_found_rows($search);
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
        $query = $this->reports->newQuery();

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

    public function exportReport(Request $request)
    {
        $reportType = $request->input('report_type', 'daily_floor_stock_report');
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

        // get rows and headers for export (plain text values)
        [$dataRows, $headers] = $this->getReportDataForExport($reportType, $filters);

        $user = Auth::user();
        $metaInfo = [
            'date_range' => $selectedDate ?: 'All time',
            'generated_by' => $user->fullname ?? ($user->name ?? 'System'),
        ];

        $fileName = 'report_'.$reportType.'_'.now()->format('Ymd_His').'.xlsx';

        return Excel::download(new ProductExport($dataRows, $metaInfo, $headers), $fileName);
    }

    public function exportDefectReport(Request $request)
    {
        $filters = [
            'stages' => $request->get('stage') ?? '',
        ];

        $selectedDate = $request->get('selectedDaterange') ?? $request->get('default_dateRange');
        $daterange = LocaleHelper::dateRangeDateInputFormat($selectedDate);
        if ($daterange) {
            $filters['start_date'] = $daterange['start_date'] ?? '';
            $filters['end_date'] = $daterange['end_date'] ?? '';
        }

        $headers = [
            'Product Name',
            'SKU',
            'Size',
            'QA Code',
            'Quantity',
            'QC Status',
            'Current Stage',
            'Defect Points',
            'Changed Date',
        ];

        $defectiveItems = $this->reports->getDefectiveProducts($filters);

        if ($defectiveItems->isEmpty()) {
            return back()->with('error', 'No defective items found for the selected criteria.');
        }

        // ✅ Group items by date (Y-m-d)
        $groupedData = $defectiveItems->groupBy(function ($item) {
            return \Carbon\Carbon::parse($item->last_changed_at)->format('Y-m-d');
        });

        $dataRows = [];

        foreach ($groupedData as $date => $items) {
            // ✅ Add a section header row for each date
            $dataRows[] = ["Date: $date", '', '', '', '', '', '', '', ''];

            foreach ($items as $item) {
                // Latest history
                $history = ProductProcessHistory::where('product_id', $item->id ?? ($item->product_id ?? null))
                    ->latest('changed_at')
                    ->first();

                $status = $history->status ?? ($item->status ?? '');
                $stageName = LocaleHelper::getStageName($history->stages ?? ($item->stages ?? '')) ?? '';

                // ✅ Decode and join defect points
                $defectPoints = '';
                if (! empty($item->defects_points)) {
                    $decoded = json_decode($item->defects_points, true);
                    $defectPoints = is_array($decoded) ? implode(', ', $decoded) : $item->defects_points;
                }

                $createdAt = '';
                if (! empty($item->last_changed_at)) {
                    try {
                        $createdAt = \Carbon\Carbon::parse($item->last_changed_at)->format('Y-m-d H:i:s');
                    } catch (\Throwable $e) {
                        $createdAt = (string) ($item->last_changed_at ?? '');
                    }
                }

                $dataRows[] = [
                    $item->product_name ?? '',
                    $item->sku ?? '',
                    $item->size ?? '',
                    $item->qa_code ?? '',
                    $item->quantity ?? '',
                    $status,
                    $stageName,
                    $defectPoints,
                    $createdAt,
                ];
            }

            // Blank line between date groups
            $dataRows[] = ['', '', '', '', '', '', '', '', ''];
        }

        $user = Auth::user();
        $metaInfo = [
            'date_range' => $selectedDate ?: 'All time',
            'generated_by' => $user->fullname ?? ($user->name ?? 'System'),
        ];

        $fileName = 'defects_report_'.now()->format('Ymd_His').'.xlsx';

        return Excel::download(new ProductExport($dataRows, $metaInfo, $headers), $fileName);
    }

    /**
     * Prepare plain data rows and headers for export based on report type and filters.
     * Returns an array: [ $dataRows, $headers ]
     */
    protected function getReportDataForExport(string $reportType, array $filters): array
    {
        $dataRows = [];
        $headers = [
            'Product Name',
            'SKU',
            'Size',
            'QA Code',
            'Quantity',
            'QC Status',
            'Current Stage',
            'Created At',
        ];

        // empty search / use internal model methods; adapt as needed
        $search = '';
        $limit = 200;    // pass 0 or large number depending on model implementation
        $offset = 0;
        $sort = 'created_at';
        $order = 'desc';

        // print_r($reportType);
        // exit;
        switch ($reportType) {
            case 'daily_floor_stock_report':
                $items = $this->reports->daily_floor_stock_report_search($search, $filters, $limit, $offset, $sort, $order, $reportType);
                break;

            case 'daily_bonding_report':
                $filters['stages'] = 'bonding_qc';
                $items = $this->reports->getCommonStockReport($search, $filters, $limit, $offset, $sort, $order);
                break;

            case 'all_bonding_report':
                $filters['stages'] = 'bonding_qc';
                $filters['start_date'] = '';
                $filters['end_date'] = '';
                $items = $this->reports->getCommonStockReport($search, $filters, $limit, $offset, $sort, $order);
                break;
            case 'floor_stock_bonding':
                $filters['stages'] = 'bonding_qc';
                $filters['status'] = 'PENDING';
                $filters['start_date'] = '';
                $filters['end_date'] = '';
                $items = $this->reports->getCommonStockReport($search, $filters, $limit, $offset, $sort, $order);
                break;
            case 'daily_packing_report':
                $filters['stages'] = 'packaging';
                $items = $this->reports->getCommonStockReport($search, $filters, $limit, $offset, $sort, $order);
                break;

            case 'daily_tapedge_report':
                $filters['stages'] = 'tape_edge_qc';
                $items = $this->reports->getCommonStockReport($search, $filters, $limit, $offset, $sort, $order);
                break;

            case 'daily_zip_cover_report':
                $filters['stages'] = 'zip_cover_qc';
                $items = $this->reports->getCommonStockReport($search, $filters, $limit, $offset, $sort, $order);
                break;

            case 'monthly_yearly_report':
                $filters['start_date'] = '';
                $filters['end_date'] = '';
                $items = $this->reports->getCommonStockReport($search, $filters, $limit, $offset, $sort, $order, $reportType);
                // $items = $this->reports->getCommonStockReport($search, $filters, $limit, $offset, $sort, $order);
                break;

            default:
                // fallback to generic search
                $items = $this->reports->daily_floor_stock_report_search($search, $filters, $limit, $offset, $sort, $order, $reportType);
                break;
        }
        // print_r($items);
        // exit;
        // Normalize $items (could be Collection or array)
        foreach ($items as $item) {
            $dataRows[] = [
                $item->product_name ?? '',
                $item->sku ?? '',
                $item->size ?? '',
                $item->qa_code ?? '',
                $item->quantity ?? '',
                $item->status ?? '',
                $item->stages ?? '',
                $item->created_at ?? '',
            ];
        }

        return [$dataRows, $headers];
    }
}
