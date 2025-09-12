@extends("layouts/contentNavbarLayout")

@section("title", "Dashboard - Analytics")

@section("vendor-style")
    <link rel="stylesheet" href="{{ asset("assets/vendor/libs/apex-charts/apex-charts.css") }}">
@endsection

@section("vendor-script")
    <script src="{{ asset("assets/vendor/libs/apex-charts/apexcharts.js") }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
@endsection

@section("page-script")
    <script>
        // Data passed from server
        let metrics = @json($metrics ?? (isset($totalProducts) ? compact("totalProducts") : []));
    </script>
    @include("content.dashboard.script")
    {{-- <script src="{{ asset("assets/js/dashboard-mattress.js") }}"></script> --}}
@endsection

@section("content")
    <div class="row">
        <!-- KPI cards -->
        <div class="col-sm-6 col-lg-3 mb-4">

            {{-- <div class="card card-border-shadow-primary h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-2 pb-1">
                        <div class="avatar me-2">
                            <span class="avatar-initial bg-label-primary rounded"><i class="bx bx-store-alt"></i></span>
                        </div>
                        <h4 class="mb-0 ms-1">42</h4>
                    </div>
                    <p class="mb-1">Total Floor Stock Report</p>
                    <p class="mb-0">
                        <span class="fw-medium me-1">+18.2%</span>
                        <small class="text-muted">than last week</small>
                    </p>
                </div>
            </div> --}}


            <div class="card h-100">
                <div class="card-body">
                    <h6 class="d-flex align-items-center">
                        <div class="avatar me-3 flex-shrink-0">
                            <span class="avatar-initial bg-label-primary rounded"><i
                                    class="icon-base bx bx-cube icon-lg"></i></span>
                        </div> Total Products
                    </h6>
                    <h3 id="kpi-total-products">{{ $metrics["totalProducts"] ?? 0 }}</h3>
                    <small class="text-muted">Floor stock</small>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-lg-3 mb-4">
            <div class="card h-100">
                <div class="card-body">
                    {{-- <h6>Items in Production</h6> --}}
                    <h6 class="d-flex align-items-center">
                        <div class="avatar me-3 flex-shrink-0">
                            <span class="avatar-initial bg-label-warning rounded"><i
                                    class="icon-base bx bx-store-alt icon-lg"></i></span>
                        </div> Items in Production
                    </h6>
                    <h3 id="kpi-in-production">
                        {{ array_sum(array_filter($metrics["stageCounts"] ?? [], function ($k) {return true;})) -(($metrics["stageCounts"]["Shipped"] ?? 0) + ($metrics["stageCounts"]["Ready for Shipment"] ?? 0)) }}
                    </h3>
                    <small class="text-muted">Excludes shipped/ready</small>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-lg-3 mb-4">
            <div class="card h-100">
                <div class="card-body">
                    {{-- <h6>QC Pass Rate</h6> --}}
                    <h6 class="d-flex align-items-center">
                        <div class="avatar me-3 flex-shrink-0">
                            <span class="avatar-initial bg-label-success rounded"><i
                                    class="icon-base bx bx-check-circle icon-lg"></i></span>
                        </div> QC Pass Rate
                    </h6>
                    <h3 id="kpi-qc-pass">{{ $metrics["qcPassRate"] !== null ? $metrics["qcPassRate"] . "%" : "N/A" }}</h3>
                    <small class="text-muted">Across QC events</small>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-lg-3 mb-4">
            <div class="card h-100">
                <div class="card-body">
                    <h6 class="d-flex align-items-center">
                        <div class="avatar me-3 flex-shrink-0">
                            <span class="avatar-initial bg-label-danger rounded"><i
                                    class="icon-base bx bxs-truck icon-lg"></i></span>
                        </div> Shipped
                    </h6>
                    {{-- <h6>Shipped</h6> --}}
                    <h3 id="kpi-shipped">{{ $metrics["stageCounts"]["Shipped"] ?? 0 }}</h3>
                    <small class="text-muted">Total shipped</small>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Left: Stage distribution donut + avg times -->
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
                            @foreach ($metrics["avgStageTimes"] ?? [] as $stage => $mins)
                                <li><strong>{{ $stage }}</strong>: {{ $mins }} minutes</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Center: Daily throughput -->
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

        <!-- Right: QC status bar + stuck items -->
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
                        @foreach ($metrics["stuckItems"] as $s)
                            <li class="list-group-item">
                                <div><strong>{{ $s->sku }}</strong> — {{ $s->product_name }}</div>
                                <small>{{ $s->current_stage }} • updated
                                    {{ \Carbon\Carbon::parse($s->updated_at)->diffForHumans() }}</small>
                            </li>
                        @endforeach
                        @if (count($metrics["stuckItems"]) == 0)
                            <li class="list-group-item">None</li>
                        @endif
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent activity table -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5>Recent Process Events</h5>
                </div>
                <div class="card-body table-responsive">
                    <table class="table-striped table">
                        <thead>
                            <tr>
                                <th>Time</th>
                                <th>SKU</th>
                                <th>RFID</th>
                                <th>Stage</th>
                                <th>Status</th>
                                <th>Machine/By</th>
                                <th>Comments</th>
                            </tr>
                        </thead>
                        <tbody id="recent-activity-body">
                            @foreach ($metrics["recentActivities"] as $act)
                                <tr>
                                    <td>{{ \Carbon\Carbon::parse($act->changed_at)->toDateTimeString() }}</td>
                                    <td>{{ $act->sku }}</td>
                                    <td>{{ $act->rfid_tag }}</td>
                                    <td>{{ $act->stage }}</td>
                                    <td>{{ $act->status }}</td>
                                    <td>{{ $act->machine_no }} / {{ $act->changed_by }}</td>
                                    <td>{{ Str::limit($act->comments, 80) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
