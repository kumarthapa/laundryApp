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
                title: "Recent Inventory Activity", // table title
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
                        <h6 class="mb-0">Total Items</h6>
                    </div>
                    <h3 id="daily_bonding">{{ $metrics['total_inventory'] ?? 0 }}</h3>
                    <small class="text-muted">Total inventory tags count</small>
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
                        <h6 class="mb-0">Total Items Category</h6>
                    </div>
                    <h3 id="daily_tape_edge_qc">{{ $metrics['total_products'] ?? 0 }}</h3>
                    <small class="text-muted">Total Items Types</small>
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
                        <h6 class="mb-0">Clean Items</h6>
                    </div>
                    <h3 id="daily_zip_cover_qc">{{ $metrics['total_clean_products'] ?? 0 }}</h3>
                    <small class="text-muted">Total clean items count</small>
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
                                <i class="icon-base bx bx-book-open fs-5"></i>
                            </span>
                        </div>
                        <h6 class="mb-0">Used Items</h6>
                    </div>
                    <h3 id="daily_packaging">{{ $metrics['total_dirty_products'] ?? 0 }}</h3>
                    <small class="text-muted">Total used items count</small>
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
                        <h6 class="mb-0">Unused Items</h6>
                    </div>
                    <h3 id="total_packaging">{{ $metrics['total_new_products'] ?? 0 }}</h3>
                    <small class="text-muted">Total unused items count</small>
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
                        <h6 class="mb-0">Damaged Items</h6>
                    </div>
                    <h3 id="damaged_products">{{ $metrics['total_damaged_products'] ?? 0 }}</h3>
                    <small class="text-muted">Total damaged items count</small>
                </div>
            </div>
        </div>
    </div>




    <!-- ==== DataTable Section ==== -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <!-- Filters -->
                {{-- <div class="card-header border-bottom">
                    <div class="d-md-flex justify-content-between align-items-center gap-3 pt-3" id="filter-container">
                        <div class="input-group date">
                            <input class="form-control filter-selected-data" type="text"
                                name="masterTableDaterangePicker" placeholder="DD/MM/YY" id="selectedDaterange" />
                            <span class="input-group-text">
                                <i class='bx bxs-calendar'></i>
                            </span>
                        </div>
                    </div>
                </div> --}}

                <!-- DataTable displaying process history -->
                <div class="card-datatable table-responsive pt-0">
                    <table class="datatables-basic border-top table" id="DataTables2025"></table>
                </div>
            </div>
        </div>
    </div>
@endsection
