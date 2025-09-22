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
        // Data passed from server for initial page load
        let metrics = @json($metrics);
    </script>
    @include("content.dashboard.script")
@endsection

@section("content")
    <div class="row">
        <div class="col-sm-6 col-lg-3 mb-4">
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
                    <h6 class="d-flex align-items-center">
                        <div class="avatar me-3 flex-shrink-0">
                            <span class="avatar-initial bg-label-warning rounded"><i
                                    class="icon-base bx bx-store-alt icon-lg"></i></span>
                        </div> Items in Production
                    </h6>
                    <h3 id="kpi-in-production">
                        @php
                            $total = array_sum($metrics["stageCounts"] ?? []);
                            $shippedOrReady =
                                ($metrics["stageCounts"]["Shipped"] ?? 0) +
                                ($metrics["stageCounts"]["Ready for Shipment"] ?? 0);
                            echo $total - $shippedOrReady;
                        @endphp
                    </h3>
                    <small class="text-muted">Excludes shipped/ready</small>
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
                        </div> QC Pass Rate
                    </h6>
                    <h3 id="kpi-qc-pass">{{ isset($metrics["qcPassRate"]) ? $metrics["qcPassRate"] . "%" : "N/A" }}</h3>
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
                    <h3 id="kpi-shipped">{{ $metrics["stageCounts"]["Shipped"] ?? 0 }}</h3>
                    <small class="text-muted">Total shipped</small>
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
                                <li><strong>{{ $stage }}</strong>: {{ $mins }} minutes</li>
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
                                <th>Updated Date</th>
                                <th>SKU</th>
                                <th>RFID</th>
                                <th>Stage</th>
                                <th>Status</th>
                                <th>Comments</th>
                            </tr>
                        </thead>
                        <tbody id="recent-activity-body">
                            @foreach ($metrics["recentActivities"] as $act)
                                <tr>
                                    <td>{{ $act->changed_at }}</td>
                                    <td>{{ $act->sku }}</td>
                                    <td>{{ $act->rfid_tag }}</td>
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
    </div>
@endsection
