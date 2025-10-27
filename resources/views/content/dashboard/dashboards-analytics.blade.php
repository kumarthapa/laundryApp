@extends('layouts/contentNavbarLayout')

@section('title', 'Dashboard - Analytics')

@section('vendor-style')
    <!-- Vendor CSS for charts and datatable styling -->
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/apex-charts/apex-charts.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/datatables.bootstrap5.css') }}">
@endsection

@section('vendor-script')
    <!-- Vendor JS libraries -->
    <script src="{{ asset('assets/vendor/libs/apex-charts/apexcharts.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
@endsection

@section('page-script')
    <!-- Datatable setup partial -->
    @include('content.partial.datatable')

    <!-- Date range picker setup partial -->
    @include('content.common.scripts.daterangePicker', [
        'float' => 'right',
        'name' => 'masterTableDaterangePicker',
    ])

    <script>
        // Dashboard metrics data passed from Laravel controller
        let metrics = @json($metrics);

        $(document).ready(function() {
            // Initialize DataTable headers passed from controller
            var tableHeaders = {!! $table_headers !!};

            // Datatable configuration options
            var options1 = {
                url: "{{ route('dashboard.list') }}", // API route to fetch table data
                createPermissions: '', // not used here
                fetchId: "FetchData",
                title: "Recent Process Events", // table title
                displayLength: 30,
                is_export: "Export All", // export label
            };

            // Load filter dropdown data from backend
            var statusData = @json($status ?? []);
            var stageData = @json($stages ?? []);
            var defectPointsData = @json($defect_points ?? []);

            // Convert [{name, value}] array to {key:value} format for dropdowns
            function arrayToKeyValue(arr) {
                var obj = {
                    'ALL': 'ALL'
                }; // default ALL filter
                if (Array.isArray(arr)) {
                    arr.forEach(function(item) {
                        obj[item.value] = item.name;
                    });
                }
                return obj;
            }

            // Flatten defect_points object-of-arrays into single key:value structure
            function defectPointsToKeyValue(obj) {
                var result = {
                    'ALL': 'ALL'
                };
                if (obj && typeof obj === 'object') {
                    Object.values(obj).forEach(function(arr) {
                        arr.forEach(function(item) {
                            if (item.value && item.name)
                                result[item.value] = item.name;
                        });
                    });
                }
                return result;
            }

            // Define filter data used in table
            var filterData = {
                'qc_status': {
                    'data': arrayToKeyValue(statusData),
                    'filter_name': 'Filter By Status',
                },
                'current_stage': {
                    'data': arrayToKeyValue(stageData),
                    'filter_name': 'Filter By Stage',
                },
                'defect_points': {
                    'data': defectPointsToKeyValue(defectPointsData),
                    'filter_name': 'Filter By Defect Point',
                },
            };

            // Initialize the main datatable with filters and stats callback
            getDataTableS(options1, filterData, tableHeaders, getStats);

            // Optional callback for debugging active filters
            function getStats(params) {
                // console.log("Applied filters:", params);
            }

            // Export button handler - includes date range if selected
            let exportUrl = "{{ route('dashboard.exportProducts') }}";

            $(".exportBtn").click(function() {
                // collect params
                const params = {};

                const selectedDaterange = $('#selectedDaterange').val();
                if (selectedDaterange) params.daterange = selectedDaterange;

                const status = $('#qc_statusFilter').length ? $('#qc_statusFilter').val() : '';
                if (status && status !== 'all') params.status = status;

                const stage = $('#current_stageFilter').length ? $('#current_stageFilter').val() : '';
                if (stage && stage !== 'all') params.stage = stage;

                // optional: include search or report_type if present on page
                const search = $('input[type="search"]').length ? $('input[type="search"]').val() : '';
                if (search) params.search = search;


                const defect_points = $('#defect_pointsFilter').length ? $('#defect_pointsFilter').val() :
                    '';
                if (defect_points && defect_points !== 'all') params.defect_points = defect_points;

                const reportType = $('#reportType').length ? $('#reportType').val() : '';
                if (reportType) params.report_type = reportType;

                // build query string
                const qs = Object.keys(params)
                    .map(k => encodeURIComponent(k) + '=' + encodeURIComponent(params[k]))
                    .join('&');

                // redirect to export url (with params if any)
                window.location.href = qs ? `${exportUrl}?${qs}` : exportUrl;
            });

        });
    </script>

    <!-- Dashboard chart rendering logic -->
    @include('content.dashboard.script')
@endsection

@section('content')
    <div class="row">
        <!-- ==== Metric Summary Cards ==== -->
        <!-- Each card shows quick daily or total counts (last 30 days) -->

        <!-- Daily Bonding count -->
        <div class="col-sm-6 col-lg-2 mb-4">
            <div class="card h-100">
                <div class="card-body">
                    <h6 class="d-flex align-items-center">
                        <div class="avatar me-3 flex-shrink-0">
                            <span class="avatar-initial bg-label-warning rounded">
                                <i class="icon-base bx bx-store-alt icon-lg"></i>
                            </span>
                        </div> Daily Bonding
                    </h6>
                    <h3 id="daily_bonding">{{ $metrics['daily_bonding'] ?? 0 }}</h3>
                    <small class="text-muted">Daily bonding check records</small>
                </div>
            </div>
        </div>

        <!-- Daily Tape Edge -->
        <div class="col-sm-6 col-lg-2 mb-4">
            <div class="card h-100">
                <div class="card-body">
                    <h6 class="d-flex align-items-center">
                        <div class="avatar me-3 flex-shrink-0">
                            <span class="avatar-initial bg-label-primary rounded">
                                <i class="icon-base bx bx-cube icon-lg"></i>
                            </span>
                        </div>Daily Tape Edge mattress
                    </h6>
                    <h3 id="daily_tape_edge_qc">{{ $metrics['daily_tape_edge_qc'] ?? 0 }}</h3>
                    <small class="text-muted">Daily Tape Edge check records</small>
                </div>
            </div>
        </div>

        <!-- Daily Zip Cover -->
        <div class="col-sm-6 col-lg-2 mb-4">
            <div class="card h-100">
                <div class="card-body">
                    <h6 class="d-flex align-items-center">
                        <div class="avatar me-3 flex-shrink-0">
                            <span class="avatar-initial bg-label-success rounded">
                                <i class="icon-base bx bx-check-circle icon-lg"></i>
                            </span>
                        </div> Daily Zip Cover mattress
                    </h6>
                    <h3 id="daily_zip_cover_qc">{{ $metrics['daily_zip_cover_qc'] ?? 0 }}</h3>
                    <small class="text-muted">Daily Zip Cover check records</small>
                </div>
            </div>
        </div>

        <!-- Daily Packaging -->
        <div class="col-sm-6 col-lg-2 mb-4">
            <div class="card h-100">
                <div class="card-body">
                    <h6 class="d-flex align-items-center">
                        <div class="avatar me-3 flex-shrink-0">
                            <span class="avatar-initial bg-label-dark rounded">
                                <i class="icon-base bx bxs-truck icon-lg"></i>
                            </span>
                        </div> Daily Packing
                    </h6>
                    <h3 id="daily_packaging">{{ $metrics['daily_packaging'] ?? 0 }}</h3>
                    <small class="text-muted">Daily Packing check records</small>
                </div>
            </div>
        </div>

        <!-- Total Packaging (last 30 days) -->
        <div class="col-sm-6 col-lg-2 mb-4">
            <div class="card h-100">
                <div class="card-body">
                    <h6 class="d-flex align-items-center">
                        <div class="avatar me-3 flex-shrink-0">
                            <span class="avatar-initial bg-label-success rounded">
                                <i class="icon-base bx bxs-truck icon-lg"></i>
                            </span>
                        </div> Total Packing
                    </h6>
                    <h3 id="total_packaging">{{ $metrics['total_packaging'] ?? 0 }}</h3>
                    <small class="text-muted">Total Packing last 30 days</small>
                </div>
            </div>
        </div>

        <!-- Reprocessed Products -->
        <div class="col-sm-6 col-lg-2 mb-4">
            <div class="card h-100">
                <div class="card-body">
                    <h6 class="d-flex align-items-center">
                        <div class="avatar me-3 flex-shrink-0">
                            <span class="avatar-initial bg-label-danger rounded">
                                <i class="icon-base bx bx-error icon-lg"></i>
                            </span>
                        </div>Reprocessing mattress
                    </h6>
                    <h3 id="reprocessed_products">{{ $metrics['reprocessed_products'] ?? 0 }}</h3>
                    <small class="text-muted">Reprocessed Records last 30 days</small>
                </div>
            </div>
        </div>
    </div>

    <!-- ==== Charts Section ==== -->
    <div class="row">
        <!-- Overall stage distribution donut chart -->
        <div class="col-sm-6 col-lg-4 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5>Mattress Stage Distribution (last 30 days)</h5>
                </div>
                <div class="card-body">
                    <div id="stageDonut"></div>
                    <div class="mt-2 py-2">
                        <h6 class="text-muted text-center py-1">Production Process Stages Breakdown</h6>
                    </div>
                </div>
            </div>
        </div>

        <?php
        // Label map for stage-wise chart titles
        $labelString = [
            'bonding_qc' => 'Bonding',
            'tape_edge_qc' => 'Tape Edge',
            'zip_cover_qc' => 'Zip Cover',
            'packaging' => 'Packing',
        ];
        ?>

        <!-- Dynamic QC stage charts -->
        @if (isset($metrics['qc_stage_series']) && count($metrics['qc_stage_series']) > 0)
            @foreach ($metrics['qc_stage_series'] as $stage => $series)
                <div class="col-sm-6 col-lg-4 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <h5>{{ $labelString[$stage] ?? $stage }} (last 30 days)</h5>
                        </div>
                        <div class="card-body">
                            <div id="qc-{{ \Illuminate\Support\Str::slug($stage, '_') }}" style="min-height:320px"></div>
                        </div>
                    </div>
                </div>
            @endforeach
        @else
            <!-- Message when no chart data is available -->
            <div class="col-sm-6 col-lg-4 mb-4">
                <div class="alert alert-info">
                    No QC data available to display charts.
                </div>
            </div>
        @endif
    </div>

    <!-- ==== DataTable Section ==== -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <!-- Filters -->
                <div class="card-header border-bottom">
                    <div class="d-md-flex justify-content-between align-items-center gap-3 pt-3" id="filter-container">
                        <div class="input-group date">
                            <input class="form-control filter-selected-data" type="text"
                                name="masterTableDaterangePicker" placeholder="DD/MM/YY" id="selectedDaterange" />
                            <span class="input-group-text">
                                <i class='bx bxs-calendar'></i>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- DataTable displaying process history -->
                <div class="card-datatable table-responsive pt-0">
                    <table class="datatables-basic border-top table" id="DataTables2024"></table>
                </div>
            </div>
        </div>
    </div>
@endsection
