<?php

namespace App\Http\Controllers\dashboard;

use App\Helpers\LocaleHelper;
use App\Helpers\TableHelper;
use App\Http\Controllers\Controller;
use App\Models\inventory\Inventory;
use App\Models\products\Product;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; // Assuming this is where getProductStagesAndDefectPoints is defined

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $headers = [
            ['updated_date' => 'Updated Date'],
            ['product_name' => 'Product Name'],
            ['sku' => 'SKU'],
            ['epc_code' => 'EPC / Tag'],
            // ['location_name' => 'Location'],
            ['movement' => 'Movement'],      // IN / OUT badge
            ['status' => 'Status'],
            ['life_cycles' => 'Life Cycles'],
            // ['adjust_qty' => 'Qty'],
            // ['activity_id' => 'Activity ID'],
            ['remarks' => 'Remarks'],
            // ['actions' => 'Actions'] // optional
        ];

        $table_headers = TableHelper::get_manage_table_headers($headers, false, true, false, false, false);

        // ================================
        // 📌 INVENTORY METRICS
        // ================================
        $metrics = [
            'total_inventory' => Inventory::count(),
            'total_products' => Product::count(),

            // Status-wise inventory counts
            'total_new_products' => Inventory::where('status', 'new')->count(),
            'total_clean_products' => Inventory::where('status', 'clean')->count(),
            'total_dirty_products' => Inventory::where('status', 'dirty')->count(),
            'total_damaged_products' => Inventory::where('status', 'damaged')->count(),
        ];

        return view('content.dashboard.dashboards-analytics', [
            'metrics' => $metrics,
            'table_headers' => $table_headers,
        ]);
    }

    /**
     * Build a single row for datatable from DB row/stdClass
     */
    protected function tableHeaderRowData($row)
    {
        $data = [];
        $data['updated_date'] = LocaleHelper::formatDateWithTime($row->updated_date ?? '');
        $data['product_name'] = e($row->product_name ?? '-');
        $data['sku'] = e($row->sku ?? '-');
        $data['epc_code'] = e($row->epc_code ?? '-');

        // Location
        // $data['location_name'] = e($row->location_name ?? '-');

        // Movement badge (prefer to use trans_type or inward/outward fields)
        $movementBadge = '';
        if (! empty($row->inward) && intval($row->inward) > 0) {
            $movementBadge = '<span class="badge bg-label-danger">Inward</span>';
        } elseif (! empty($row->outward) && intval($row->outward) > 0) {
            $movementBadge = '<span class="badge bg-label-success">Outward</span>';
        } elseif (! empty($row->trans_type)) {
            // fallback to trans_type
            $mt = e($row->trans_type);
            $cls = in_array($mt, ['status_dirty_in', 'fixed_reader_in']) ? 'bg-label-danger' : 'bg-label-success';
            $movementBadge = '<span class="badge '.$cls.'">'.$mt.'</span>';
        }
        $data['movement'] = $movementBadge;

        // Status badge
        $data['status'] = $this->getStatusBadge($row->status);

        // Life cycles
        $data['life_cycles'] = e($row->life_cycles ?? '-');

        // Qty
        // $data['adjust_qty'] = isset($row->adjust_qty) ? number_format(intval($row->adjust_qty)) : '0';

        // Activity ID (linkable for audit)
        $activityId = $row->activity_id ?? null;
        // $data['activity_id'] = $activityId ? '<a href="'.route('inventory', ['id' => $activityId]).'">'.$activityId.'</a>' : '-';

        // Remarks truncated
        $data['remarks'] = $row->remarks ? e(\Illuminate\Support\Str::limit($row->remarks, 60)) : '';

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

        // If status is new rename Unused, if status is dirty rename Used
        if ($status === 'new') {
            $label = 'Unused';
        } elseif ($status === 'dirty') {
            $label = 'Used';
        }

        return '<span class="badge bg-label-'.$color.'">'.$label.'</span>';
    }

    public function list(Request $request)
    {
        $search = $request->get('search', '');
        $limit = intval($request->get('length', 100));
        $offset = intval($request->get('start', 0));
        $sort = $request->get('sort', 'ia.created_at');
        $order = $request->get('order', 'desc');

        // date range
        $selectedDate = $request->get('selectedDaterange') ?? $request->get('default_dateRange');
        $daterange = LocaleHelper::dateRangeDateInputFormat($selectedDate);
        $startDate = $daterange['start_date'] ?? Carbon::now()->subMonth()->startOfDay();
        $endDate = $daterange['end_date'] ?? Carbon::now()->endOfDay();

        // Base query: inventory_activity JOIN products LEFT JOIN rfid_tags
        $query = DB::table('inventory_activity as ia')
            ->join('products as p', 'p.id', '=', 'ia.product_id')
            ->leftJoin('rfid_tags as t', 't.id', '=', 'ia.inventory_id')
            ->leftJoin('locations as l', 'l.location_id', '=', 'ia.location_id')   // add location
            ->select(
                DB::raw("DATE_FORMAT(ia.created_at, '%Y-%m-%d %H:%i:%s') as updated_date"),
                'p.product_name',
                'p.sku',
                't.epc_code',
                'l.location_name as location_name',           // new
                'ia.trans_type',
                'ia.opening_stock',
                'ia.inward',
                'ia.outward',
                'ia.adjust_qty',
                'ia.closing_stock',
                'ia.remarks',
                'ia.status',
                't.life_cycles',
                'ia.trans_id as activity_id'               // new
            );

        // Search across useful columns
        if (! empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('p.product_name', 'like', "%{$search}%")
                    ->orWhere('p.sku', 'like', "%{$search}%")
                    ->orWhere('t.epc_code', 'like', "%{$search}%")
                    ->orWhere('ia.trans_type', 'like', "%{$search}%")
                    ->orWhere('ia.remarks', 'like', "%{$search}%");
            });
        }

        // Date filter
        if (! empty($startDate) && ! empty($endDate)) {
            $query->whereBetween('ia.created_at', [$startDate, $endDate]);
        }

        // Optional filters from request
        if ($request->filled('product_id')) {
            $query->where('ia.product_id', intval($request->get('product_id')));
        }
        if ($request->filled('location_id')) {
            $query->where('ia.location_id', intval($request->get('location_id')));
        }
        if ($request->filled('trans_type')) {
            $query->where('ia.trans_type', $request->get('trans_type'));
        }

        // protect sort column (whitelist)
        $allowedSorts = [
            'ia.created_at', 'p.product_name', 'p.sku', 't.epc_code',
            'ia.trans_type', 'ia.opening_stock', 'ia.inward', 'ia.outward', 'ia.closing_stock',
        ];
        if (! in_array($sort, $allowedSorts, true)) {
            $sort = 'ia.created_at';
        }
        $order = strtolower($order) === 'asc' ? 'asc' : 'desc';

        // total count clone
        $countQuery = DB::table('inventory_activity as ia')
            ->join('products as p', 'p.id', '=', 'ia.product_id')
            ->leftJoin('rfid_tags as t', 't.id', '=', 'ia.inventory_id');

        if (! empty($search)) {
            $countQuery->where(function ($q) use ($search) {
                $q->where('p.product_name', 'like', "%{$search}%")
                    ->orWhere('p.sku', 'like', "%{$search}%")
                    ->orWhere('t.epc_code', 'like', "%{$search}%")
                    ->orWhere('ia.trans_type', 'like', "%{$search}%")
                    ->orWhere('ia.remarks', 'like', "%{$search}%");
            });
        }
        if (! empty($startDate) && ! empty($endDate)) {
            $countQuery->whereBetween('ia.created_at', [$startDate, $endDate]);
        }
        if ($request->filled('product_id')) {
            $countQuery->where('ia.product_id', intval($request->get('product_id')));
        }
        if ($request->filled('location_id')) {
            $countQuery->where('ia.location_id', intval($request->get('location_id')));
        }
        if ($request->filled('trans_type')) {
            $countQuery->where('ia.trans_type', $request->get('trans_type'));
        }

        $total_rows = $countQuery->count();

        // Fetch rows
        $rows = $query->orderBy($sort, $order)
            ->limit($limit)
            ->offset($offset)
            ->get();

        // Format rows for datatable
        $data_rows = [];
        foreach ($rows as $row) {
            $data_rows[] = $this->tableHeaderRowData($row);
        }

        return response()->json([
            'data' => $data_rows,
            'recordsTotal' => $total_rows,
            'recordsFiltered' => $total_rows,
        ]);
    }

    // JSON endpoint for polling / refreshing charts
    public function metrics(Request $request)
    {
        $metrics = $this->gatherMetrics();

        return response()->json($metrics);
    }
}
