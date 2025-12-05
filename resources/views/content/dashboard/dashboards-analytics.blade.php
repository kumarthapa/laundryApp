@extends('layouts/contentNavbarLayout')

@section('title', 'Dashboard - Analytics')

@section('vendor-style')
    <!-- Vendor CSS for charts and datatable styling -->
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/apex-charts/apex-charts.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/datatables.bootstrap5.css') }}">

    <style>
        /* ============= Gradient Icon Backgrounds ============= */
        .gradient-warning {
            background: linear-gradient(135deg, #fbc02d, #ff9800);
        }

        .gradient-primary {
            background: linear-gradient(135deg, #4e73df, #1ccaff);
        }

        .gradient-success {
            background: linear-gradient(135deg, #00c853, #1de9b6);
        }

        .gradient-dark {
            background: linear-gradient(135deg, #616161, #212121);
        }

        .gradient-danger {
            background: linear-gradient(135deg, #e53935, #ff1744);
        }

        /* ============= Glow Effect ============= */
        .glow {
            box-shadow: 0 0 12px rgba(255, 255, 255, 0.35);
        }

        /* ============= Sneat-style Soft Card Shadow + Border ============= */
        .metric-card {
            border: 1px solid rgba(0, 0, 0, 0.08);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .metric-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
        }

        /* ============= Dark Mode Support ============= */
        [data-bs-theme="dark"] .metric-card {
            border: 1px solid rgba(255, 255, 255, 0.12);
        }

        [data-bs-theme="dark"] .glow {
            box-shadow: 0 0 14px rgba(255, 255, 255, 0.18);
        }
    </style>
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
        });
    </script>

    <!-- Dashboard chart rendering logic -->
    {{-- @include('content.dashboard.script') --}}
@endsection

@section('content')
    <div class="row mb-4 g-4">
        <!-- Daily Bonding -->
        <div class="col-12 col-sm-6 col-lg-4 col-xl-2">
            <div class="card h-100 metric-card">
                <div class="card-body">
                    <div class="d-flex align-items-center flex-nowrap flex-sm-wrap flex-md-nowrap mb-2">
                        <div class="avatar avatar-sm me-3 flex-shrink-0">
                            <span class="avatar-initial gradient-warning glow rounded">
                                <i class="icon-base bx bx-store-alt fs-5"></i>
                            </span>
                        </div>
                        <h6 class="mb-0">Daily Bonding</h6>
                    </div>
                    <h3 id="daily_bonding">{{ $metrics['daily_bonding'] ?? 0 }}</h3>
                    <small class="text-muted">Daily bonding check records</small>
                </div>
            </div>
        </div>

        <!-- Daily Tape Edge -->
        <div class="col-12 col-sm-6 col-lg-4 col-xl-2">
            <div class="card h-100 metric-card">
                <div class="card-body">
                    <div class="d-flex align-items-center flex-nowrap flex-sm-wrap flex-md-nowrap mb-2">
                        <div class="avatar avatar-sm me-3 flex-shrink-0">
                            <span class="avatar-initial gradient-primary glow rounded">
                                <i class="icon-base bx bx-cube fs-5"></i>
                            </span>
                        </div>
                        <h6 class="mb-0">Daily Tape Edge mattress</h6>
                    </div>
                    <h3 id="daily_tape_edge_qc">{{ $metrics['daily_tape_edge_qc'] ?? 0 }}</h3>
                    <small class="text-muted">Daily Tape Edge check records</small>
                </div>
            </div>
        </div>

        <!-- Daily Zip Cover -->
        <div class="col-12 col-sm-6 col-lg-4 col-xl-2">
            <div class="card h-100 metric-card">
                <div class="card-body">
                    <div class="d-flex align-items-center flex-nowrap flex-sm-wrap flex-md-nowrap mb-2">
                        <div class="avatar avatar-sm me-3 flex-shrink-0">
                            <span class="avatar-initial gradient-success glow rounded">
                                <i class="icon-base bx bx-check-circle fs-5"></i>
                            </span>
                        </div>
                        <h6 class="mb-0">Daily Zip Cover mattress</h6>
                    </div>
                    <h3 id="daily_zip_cover_qc">{{ $metrics['daily_zip_cover_qc'] ?? 0 }}</h3>
                    <small class="text-muted">Daily Zip Cover check records</small>
                </div>
            </div>
        </div>

        <!-- Daily Packaging -->
        <div class="col-12 col-sm-6 col-lg-4 col-xl-2">
            <div class="card h-100 metric-card">
                <div class="card-body">
                    <div class="d-flex align-items-center flex-nowrap flex-sm-wrap flex-md-nowrap mb-2">
                        <div class="avatar avatar-sm me-3 flex-shrink-0">
                            <span class="avatar-initial gradient-dark glow rounded">
                                <i class="icon-base bx bxs-truck fs-5"></i>
                            </span>
                        </div>
                        <h6 class="mb-0">Daily Packing</h6>
                    </div>
                    <h3 id="daily_packaging">{{ $metrics['daily_packaging'] ?? 0 }}</h3>
                    <small class="text-muted">Daily Packing check records</small>
                </div>
            </div>
        </div>

        <!-- Total Packaging -->
        <div class="col-12 col-sm-6 col-lg-4 col-xl-2">
            <div class="card h-100 metric-card">
                <div class="card-body">
                    <div class="d-flex align-items-center flex-nowrap flex-sm-wrap flex-md-nowrap mb-2">
                        <div class="avatar avatar-sm me-3 flex-shrink-0">
                            <span class="avatar-initial gradient-success glow rounded">
                                <i class="icon-base bx bxs-truck fs-5"></i>
                            </span>
                        </div>
                        <h6 class="mb-0">Total Packing</h6>
                    </div>
                    <h3 id="total_packaging">{{ $metrics['total_packaging'] ?? 0 }}</h3>
                    <small class="text-muted">Total Packing last 30 days</small>
                </div>
            </div>
        </div>

        <!-- Reprocessed Products -->
        <div class="col-12 col-sm-6 col-lg-4 col-xl-2">
            <div class="card h-100 metric-card">
                <div class="card-body">
                    <div class="d-flex align-items-center flex-nowrap flex-sm-wrap flex-md-nowrap mb-2">
                        <div class="avatar avatar-sm me-3 flex-shrink-0">
                            <span class="avatar-initial gradient-danger glow rounded">
                                <i class="icon-base bx bx-error fs-5"></i>
                            </span>
                        </div>
                        <h6 class="mb-0">Reprocessing mattress</h6>
                    </div>
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
            'bonding_qc' => 'inventory',
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
                    <table class="datatables-basic border-top table" id="DataTables2025"></table>
                </div>
            </div>
        </div>
    </div>
@endsection
