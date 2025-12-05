@extends('layouts/contentNavbarLayout')

@section('title', 'Dashboard - Analytics')

@section('vendor-style')
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/apex-charts/apex-charts.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/datatables.bootstrap5.css') }}">
@endsection

@section('vendor-script')
    <script src="{{ asset('assets/vendor/libs/apex-charts/apexcharts.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
@endsection

@section('page-script')
    @include('content.partial.datatable')
    @include('content.common.scripts.daterangePicker', [
        'float' => 'right',
        'name' => 'masterTableDaterangePicker',
    ])
    <script>
        // Data passed from server for initial page load
        let metrics = @json($metrics);

        $(document).ready(function() {
            var tableHeaders = {!! $table_headers !!};
            var options1 = {
                url: "{{ route('dashboard.list') }}",
                createPermissions: '',
                fetchId: "FetchData",
                title: "Recent Process Events",
                displayLength: 30,
                is_export: "Export All",
            };

            // Get Blade JSON
            var statusData = @json($status ?? []);
            var stageData = @json($stages ?? []);
            var defectPointsData = @json($defect_points ?? []);

            // Convert array of {name,value} to key:value object
            function arrayToKeyValue(arr) {
                var obj = {
                    'ALL': 'ALL'
                };
                if (Array.isArray(arr)) {
                    arr.forEach(function(item) {
                        obj[item.value] = item.name;
                    });
                }
                return obj;
            }

            // Convert defect points (object of arrays) to flattened key:value object
            function defectPointsToKeyValue(obj) {
                var result = {
                    'ALL': 'ALL'
                };
                if (obj && typeof obj === 'object') {
                    Object.values(obj).forEach(function(arr) {
                        arr.forEach(function(item) {
                            if (item.value && item.name) result[item.value] = item.name;
                        });
                    });
                }
                return result;
            }

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

            console.log("filterData:", filterData);

            getDataTableS(options1, filterData, tableHeaders, getStats);

            function getStats(params) {
                console.log("Applied filters:", params);
            }


            let exportUrl = "{{ route('dashboard.exportProducts') }}";
            $(".exportBtn").click(function() {
                let selectedDaterange = document.getElementById('selectedDaterange').value || '';
                if (selectedDaterange) {
                    window.location.href =
                        `${exportUrl}?daterange=${encodeURIComponent(selectedDaterange)}`;
                } else {
                    window.location.href = exportUrl;
                }

            });
        });
    </script>
    @include('content.dashboard.script')
@endsection

@section('content')
    <div class="row">


        <div class="col-sm-6 col-lg-3 mb-4">
            <div class="card h-100">
                <div class="card-body">
                    <h6 class="d-flex align-items-center">
                        <div class="avatar me-3 flex-shrink-0">
                            <span class="avatar-initial bg-label-warning rounded"><i
                                    class="icon-base bx bx-store-alt icon-lg"></i></span>
                        </div> Daily Floor Stock
                    </h6>
                    <h3 id="kpi-in-daily-production">
                        {{ $metrics['totalProductsToday'] ?? 0 }}
                    </h3>
                    {{-- <small class="text-muted">Excludes shipped/ready</small> --}}
                    <small class="text-muted">Daily SKU Generated Floor Stock</small>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-lg-3 mb-4">
            <div class="card h-100">
                <div class="card-body">
                    <h6 class="d-flex align-items-center">
                        <div class="avatar me-3 flex-shrink-0">
                            <span class="avatar-initial bg-label-primary rounded"><i
                                    class="icon-base bx bx-cube icon-lg"></i></span>
                        </div>All Time Production
                    </h6>
                    <h3 id="kpi-total-products">{{ $metrics['totalProducts'] ?? 0 }}</h3>
                    <small class="text-muted">All Time SKU Generated Stock</small>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-lg-3 mb-4">
            <div class="card h-100">
                <div class="card-body">
                    <h6 class="d-flex align-items-center">
                        <div class="avatar me-3 flex-shrink-0">
                            <span class="avatar-initial bg-label-success rounded"><i
                                    class="icon-base bx bx-check-circle icon-lg"></i></span>
                        </div> All Time Success Rate
                    </h6>
                    <h3 id="kpi-qc-pass">{{ isset($metrics['qcPassRate']) ? $metrics['qcPassRate'] . '%' : 'N/A' }}</h3>
                    <small class="text-muted">Across QC events</small>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-lg-3 mb-4">
            <div class="card h-100">
                <div class="card-body">
                    <h6 class="d-flex align-items-center">
                        <div class="avatar me-3 flex-shrink-0">
                            <span class="avatar-initial bg-label-dark rounded"><i
                                    class="icon-base bx bxs-truck icon-lg"></i></span>
                        </div>Stock In Packaging
                    </h6>
                    <h3 id="kpi-shipped">{{ $metrics['stageCounts']['packaging'] ?? 0 }}</h3>
                    <small class="text-muted">Ready To Shipped</small>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-4 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5>Stage Distribution</h5>
                </div>
                <div class="card-body">
                    <div id="stageDonut"></div>
                    <div class="mt-3">
                        <h6>Average minutes per stage</h6>
                        <ul>
                            @forelse ($metrics['avgStageTimes'] ?? [] as $stage => $mins)
                                <li><strong>{{ $LocaleHelper->getStageName($stage) }}</strong>: {{ $mins }}
                                    minutes
                                </li>
                            @empty
                                <li>No data available</li>
                            @endforelse
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-5 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5>Daily Throughput (last 30 days)</h5>
                </div>
                <div class="card-body">
                    <div id="dailyThroughput"></div>
                </div>
            </div>
        </div>

        <div class="col-lg-3 mb-4">
            <div class="card mb-3">
                <div class="card-header">
                    <h6>QC Status</h6>
                </div>
                <div class="card-body">
                    <div id="qcBar"></div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h6>Stuck Items (>{{ 7 }} days)</h6>
                </div>
                <div class="card-body">
                    <ul class="list-group">
                        @forelse ($metrics['stuckItems'] as $s)
                            <li class="list-group-item">
                                <div><strong>{{ $s->sku }}</strong> — {{ $s->product_name }}</div>
                                <small>{{ $s->current_stage }} • updated
                                    {{ \Carbon\Carbon::parse($s->updated_at)->diffForHumans() }}</small>
                            </li>
                        @empty
                            <li class="list-group-item">None</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>
    </div>

    {{-- <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5>Recent Process Events</h5>
                </div>
                <div class="card-body table-responsive">
                    <table class="table-striped table">
                        <thead>
                            <tr>
                                <th>Updated Date</th>
                                <th>SKU</th>
                                <th>QA Code</th>
                                <th>Stage</th>
                                <th>Status</th>
                                <th>Comments</th>
                            </tr>
                        </thead>
                        <tbody id="recent-activity-body">
                            @foreach ($metrics['recentActivities'] as $act)
                                <tr>
                                    <td>{{ $act->changed_at }}</td>
                                    <td>{{ $act->sku }}</td>
                                    <td>{{ $act->rfid_code }}</td>
                                    <td>{{ $act->stage }}</td>
                                    <td>{{ $act->status }}</td>
                                    <td>{{ Str::limit($act->comments, 80) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div> --}}

    <div class="row">
        <div class="col-12">
            <div class="card">
                <!--- Filters ------------ START ---------------->
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

                <!--- Filters ------------ END ---------------->
                <div class="card-datatable table-responsive pt-0">
                    <table class="datatables-basic border-top table" id="DataTables2025">
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
