@extends("layouts/contentNavbarLayout")

@section("title", "Dashboard - Analytics")

@section("vendor-style")
    <link rel="stylesheet" href="{{ asset("assets/vendor/libs/apex-charts/apex-charts.css") }}">
@endsection

@section("vendor-script")
    <script src="{{ asset("assets/vendor/libs/apex-charts/apexcharts.js") }}"></script>
@endsection

@section("page-script")
    <script src="{{ asset("assets/js/dashboards-analytics.js") }}"></script>
@endsection

@section("content")

    <div class="row">
        {{-- <div class="col-12">
            <h4>Dashboard</h4>
        </div> --}}
        <div class="col-sm-6 col-lg-3 mb-4">
            <div class="card card-border-shadow-primary h-100">
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
            </div>
        </div>
        <div class="col-sm-6 col-lg-3 mb-4">
            <div class="card card-border-shadow-warning h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-2 pb-1">
                        <div class="avatar me-2">
                            <span class="avatar-initial bg-label-warning rounded"><i class="bx bx-error"></i></span>
                        </div>
                        <h4 class="mb-0 ms-1">20</h4>
                    </div>
                    <p class="mb-1">Total Daily Tapedge</p>
                    <p class="mb-0">
                        <span class="fw-medium me-1">-8.7%</span>
                        <small class="text-muted">than last week</small>
                    </p>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3 mb-4">
            <div class="card card-border-shadow-danger h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-2 pb-1">
                        <div class="avatar me-2">
                            <span class="avatar-initial bg-label-danger rounded"><i class="bx bx bx-package"></i></span>
                        </div>
                        <h4 class="mb-0 ms-1">20</h4>
                    </div>
                    <p class="mb-1">Total Daily Zip Cover</p>
                    <p class="mb-0">
                        <span class="fw-medium me-1">+4.3%</span>
                        <small class="text-muted">than last week</small>
                    </p>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3 mb-4">
            <div class="card card-border-shadow-info h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-2 pb-1">
                        <div class="avatar me-2">
                            <span class="avatar-initial bg-label-info rounded"><i class="bx bx-archive"></i></span>
                        </div>
                        <h4 class="mb-0 ms-1">13</h4>
                    </div>
                    <p class="mb-1">Total Daily Packing Report</p>
                    <p class="mb-0">
                        <span class="fw-medium me-1">-2.5%</span>
                        <small class="text-muted">than last week</small>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8 col-xxl-7 order-0 mb-4">
            <div class="card h-100">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <div class="card-title mb-0">
                        <h5 class="m-0 me-2">Orders by Countries</h5>
                        <small class="text-muted">62 deliveries in progress</small>
                    </div>
                    <div class="dropdown">
                        <button class="btn p-0" type="button" id="ordersCountries" data-bs-toggle="dropdown"
                            aria-haspopup="true" aria-expanded="false">
                            <i class="bx bx-dots-vertical-rounded"></i>
                        </button>
                        <div class="dropdown-menu dropdown-menu-end" aria-labelledby="ordersCountries">
                            <a class="dropdown-item" href="javascript:void(0);">Select All</a>
                            <a class="dropdown-item" href="javascript:void(0);">Refresh</a>
                            <a class="dropdown-item" href="javascript:void(0);">Share</a>
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="nav-align-top">
                        <ul class="nav nav-tabs nav-fill tabs-line" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button type="button" class="nav-link active" role="tab" data-bs-toggle="tab"
                                    data-bs-target="#navs-justified-new" aria-controls="navs-justified-new"
                                    aria-selected="true">New</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button type="button" class="nav-link" role="tab" data-bs-toggle="tab"
                                    data-bs-target="#navs-justified-link-preparing"
                                    aria-controls="navs-justified-link-preparing" aria-selected="false"
                                    tabindex="-1">Preparing</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button type="button" class="nav-link" role="tab" data-bs-toggle="tab"
                                    data-bs-target="#navs-justified-link-shipping"
                                    aria-controls="navs-justified-link-shipping" aria-selected="false"
                                    tabindex="-1">Shipping</button>
                            </li>
                        </ul>
                        <div class="tab-content border-top border-0 pb-0 shadow-none">
                            <div class="tab-pane fade active show" id="navs-justified-new" role="tabpanel">
                                <ul class="timeline mb-0">
                                    <li class="timeline-item border-left-dashed ps-4">
                                        <span
                                            class="timeline-indicator-advanced timeline-indicator-success border-0 shadow-none">
                                            <i class="bx bx-check-circle mt-1"></i>
                                        </span>
                                        <div class="timeline-event pb-0 ps-0">
                                            <div class="timeline-header">
                                                <small class="text-success text-uppercase fw-medium">sender</small>
                                            </div>
                                            <h6 class="mb-2">Myrtle Ullrich</h6>
                                            <p class="text-muted mb-0">101 Boulder, California(CA), 95959</p>
                                        </div>
                                    </li>
                                    <li class="timeline-item border-transparent ps-4">
                                        <span
                                            class="timeline-indicator-advanced timeline-indicator-primary border-0 shadow-none">
                                            <i class="bx bx-map mt-1"></i>
                                        </span>
                                        <div class="timeline-event pb-0 ps-0">
                                            <div class="timeline-header">
                                                <small class="text-primary text-uppercase fw-medium">Receiver</small>
                                            </div>
                                            <h6 class="mb-2">Barry Schowalter</h6>
                                            <p class="text-muted mb-0">939 Orange, California(CA), 92118</p>
                                        </div>
                                    </li>
                                </ul>
                                <div class="border-1 border-light border-top mb-3 border-dashed"></div>
                                <ul class="timeline mb-0">
                                    <li class="timeline-item border-left-dashed ps-4">
                                        <span
                                            class="timeline-indicator-advanced timeline-indicator-success border-0 shadow-none">
                                            <i class="bx bx-check-circle mt-1"></i>
                                        </span>
                                        <div class="timeline-event pb-0 ps-0">
                                            <div class="timeline-header">
                                                <small class="text-success text-uppercase fw-medium">sender</small>
                                            </div>
                                            <h6 class="mb-2">Veronica Herman</h6>
                                            <p class="text-muted mb-0">162 Windsor, California(CA), 95492</p>
                                        </div>
                                    </li>
                                    <li class="timeline-item border-transparent ps-4">
                                        <span
                                            class="timeline-indicator-advanced timeline-indicator-primary border-0 shadow-none">
                                            <i class="bx bx-map mt-1"></i>
                                        </span>
                                        <div class="timeline-event pb-0 ps-0">
                                            <div class="timeline-header">
                                                <small class="text-primary text-uppercase fw-medium">Receiver</small>
                                            </div>
                                            <h6 class="mb-2">Helen Jacobs</h6>
                                            <p class="text-muted mb-0">487 Sunset, California(CA), 94043</p>
                                        </div>
                                    </li>
                                </ul>
                            </div>
                            <div class="tab-pane fade" id="navs-justified-link-preparing" role="tabpanel">
                                <ul class="timeline mb-0">
                                    <li class="timeline-item border-left-dashed ps-4">
                                        <span
                                            class="timeline-indicator-advanced timeline-indicator-success border-0 shadow-none">
                                            <i class="bx bx-check-circle mt-1"></i>
                                        </span>
                                        <div class="timeline-event pb-0 ps-0">
                                            <div class="timeline-header">
                                                <small class="text-success text-uppercase fw-medium">sender</small>
                                            </div>
                                            <h6 class="mb-2">Barry Schowalter</h6>
                                            <p class="text-muted mb-0">939 Orange, California(CA), 92118</p>
                                        </div>
                                    </li>
                                    <li class="timeline-item border-left-dashed border-transparent ps-4">
                                        <span
                                            class="timeline-indicator-advanced timeline-indicator-primary border-0 shadow-none">
                                            <i class="bx bx-map mt-1"></i>
                                        </span>
                                        <div class="timeline-event pb-0 ps-0">
                                            <div class="timeline-header">
                                                <small class="text-primary text-uppercase fw-medium">Receiver</small>
                                            </div>
                                            <h6 class="mb-2">Myrtle Ullrich</h6>
                                            <p class="text-muted mb-0">101 Boulder, California(CA), 95959 </p>
                                        </div>
                                    </li>
                                </ul>
                                <div class="border-1 border-light border-top mb-3 border-dashed"></div>
                                <ul class="timeline mb-0">
                                    <li class="timeline-item border-left-dashed ps-4">
                                        <span
                                            class="timeline-indicator-advanced timeline-indicator-success border-0 shadow-none">
                                            <i class="bx bx-check-circle mt-1"></i>
                                        </span>
                                        <div class="timeline-event pb-0 ps-0">
                                            <div class="timeline-header">
                                                <small class="text-success text-uppercase fw-medium">sender</small>
                                            </div>
                                            <h6 class="mb-2">Veronica Herman</h6>
                                            <p class="text-muted mb-0">162 Windsor, California(CA), 95492</p>
                                        </div>
                                    </li>
                                    <li class="timeline-item border-transparent ps-4">
                                        <span
                                            class="timeline-indicator-advanced timeline-indicator-primary border-0 shadow-none">
                                            <i class="bx bx-map mt-1"></i>
                                        </span>
                                        <div class="timeline-event pb-0 ps-0">
                                            <div class="timeline-header">
                                                <small class="text-primary text-uppercase fw-medium">Receiver</small>
                                            </div>
                                            <h6 class="mb-2">Helen Jacobs</h6>
                                            <p class="text-muted mb-0">487 Sunset, California(CA), 94043</p>
                                        </div>
                                    </li>
                                </ul>
                            </div>
                            <div class="tab-pane fade" id="navs-justified-link-shipping" role="tabpanel">
                                <ul class="timeline mb-0">
                                    <li class="timeline-item border-left-dashed ps-4">
                                        <span
                                            class="timeline-indicator-advanced timeline-indicator-success border-0 shadow-none">
                                            <i class="bx bx-check-circle mt-1"></i>
                                        </span>
                                        <div class="timeline-event pb-0 ps-0">
                                            <div class="timeline-header">
                                                <small class="text-success text-uppercase fw-medium">sender</small>
                                            </div>
                                            <h6 class="mb-2">Veronica Herman</h6>
                                            <p class="text-muted mb-0">101 Boulder, California(CA), 95959</p>
                                        </div>
                                    </li>
                                    <li class="timeline-item border-transparent ps-4">
                                        <span
                                            class="timeline-indicator-advanced timeline-indicator-primary border-0 shadow-none">
                                            <i class="bx bx-map mt-1"></i>
                                        </span>
                                        <div class="timeline-event pb-0 ps-0">
                                            <div class="timeline-header">
                                                <small class="text-primary text-uppercase fw-medium">Receiver</small>
                                            </div>
                                            <h6 class="mb-2">Barry Schowalter</h6>
                                            <p class="text-muted mb-0">939 Orange, California(CA), 92118</p>
                                        </div>
                                    </li>
                                </ul>
                                <div class="border-1 border-light border-top mb-3 border-dashed"></div>
                                <ul class="timeline mb-0">
                                    <li class="timeline-item border-left-dashed ps-4">
                                        <span
                                            class="timeline-indicator-advanced timeline-indicator-success border-0 shadow-none">
                                            <i class="bx bx-check-circle mt-1"></i>
                                        </span>
                                        <div class="timeline-event pb-0 ps-0">
                                            <div class="timeline-header">
                                                <small class="text-success text-uppercase fw-medium">sender</small>
                                            </div>
                                            <h6 class="mb-2">Myrtle Ullrich</h6>
                                            <p class="text-muted mb-0">162 Windsor, California(CA), 95492 </p>
                                        </div>
                                    </li>
                                    <li class="timeline-item border-transparent ps-4">
                                        <span
                                            class="timeline-indicator-advanced timeline-indicator-primary border-0 shadow-none">
                                            <i class="bx bx-map mt-1"></i>
                                        </span>
                                        <div class="timeline-event pb-0 ps-0">
                                            <div class="timeline-header">
                                                <small class="text-primary text-uppercase fw-medium">Receiver</small>
                                            </div>
                                            <h6 class="mb-2">Helen Jacobs</h6>
                                            <p class="text-muted mb-0">487 Sunset, California(CA), 94043</p>
                                        </div>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-6 col-xxl-5 order-xxl-2 mb-4">
            <div class="card h-100">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <div class="card-title mb-0">
                        <h5 class="m-0 me-2">Delivery Performance</h5>
                        <small class="text-muted">12% increase in this month</small>
                    </div>
                    <div class="dropdown">
                        <button class="btn p-0" type="button" id="deliveryPerformance" data-bs-toggle="dropdown"
                            aria-haspopup="true" aria-expanded="false">
                            <i class="bx bx-dots-vertical-rounded"></i>
                        </button>
                        <div class="dropdown-menu dropdown-menu-end" aria-labelledby="deliveryPerformance">
                            <a class="dropdown-item" href="javascript:void(0);">Select All</a>
                            <a class="dropdown-item" href="javascript:void(0);">Refresh</a>
                            <a class="dropdown-item" href="javascript:void(0);">Share</a>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <ul class="m-0 p-0">
                        <li class="d-flex mb-4 pb-1">
                            <div class="avatar me-3 flex-shrink-0">
                                <span class="avatar-initial bg-label-primary rounded"><i class="bx bx-package"></i></span>
                            </div>
                            <div class="d-flex w-100 align-items-center justify-content-between flex-wrap gap-2">
                                <div class="me-2">
                                    <h6 class="fw-normal mb-1">Packages in transit</h6>
                                    <small class="text-success fw-normal d-block">
                                        <i class="bx bx-chevron-up"></i>
                                        25.8%
                                    </small>
                                </div>
                                <div class="user-progress">
                                    <h6 class="mb-0">10k</h6>
                                </div>
                            </div>
                        </li>
                        <li class="d-flex mb-4 pb-1">
                            <div class="avatar me-3 flex-shrink-0">
                                <span class="avatar-initial bg-label-info rounded"><i class="bx bxs-truck"></i></span>
                            </div>
                            <div class="d-flex w-100 align-items-center justify-content-between flex-wrap gap-2">
                                <div class="me-2">
                                    <h6 class="fw-normal mb-1">Packages out for delivery</h6>
                                    <small class="text-success fw-normal d-block">
                                        <i class="bx bx-chevron-up"></i>
                                        4.3%
                                    </small>
                                </div>
                                <div class="user-progress">
                                    <h6 class="mb-0">5k</h6>
                                </div>
                            </div>
                        </li>
                        <li class="d-flex mb-4 pb-1">
                            <div class="avatar me-3 flex-shrink-0">
                                <span class="avatar-initial bg-label-success rounded"><i
                                        class="bx bx-check-circle"></i></span>
                            </div>
                            <div class="d-flex w-100 align-items-center justify-content-between flex-wrap gap-2">
                                <div class="me-2">
                                    <h6 class="fw-normal mb-1">Packages delivered</h6>
                                    <small class="text-danger fw-normal d-block">
                                        <i class="bx bx-chevron-down"></i>
                                        12.5
                                    </small>
                                </div>
                                <div class="user-progress">
                                    <h6 class="mb-0">15k</h6>
                                </div>
                            </div>
                        </li>
                        <li class="d-flex mb-4 pb-1">
                            <div class="avatar me-3 flex-shrink-0">
                                <span class="avatar-initial bg-label-warning rounded"><i>%</i></span>
                            </div>
                            <div class="d-flex w-100 align-items-center justify-content-between flex-wrap gap-2">
                                <div class="me-2">
                                    <h6 class="fw-normal mb-1">Delivery success rate</h6>
                                    <small class="text-success fw-normal d-block">
                                        <i class="bx bx-chevron-up"></i>
                                        35.6%
                                    </small>
                                </div>
                                <div class="user-progress">
                                    <h6 class="mb-0">95%</h6>
                                </div>
                            </div>
                        </li>
                        <li class="d-flex mb-4 pb-1">
                            <div class="avatar me-3 flex-shrink-0">
                                <span class="avatar-initial bg-label-secondary rounded"><i
                                        class="bx bx-time-five"></i></span>
                            </div>
                            <div class="d-flex w-100 align-items-center justify-content-between flex-wrap gap-2">
                                <div class="me-2">
                                    <h6 class="fw-normal mb-1">Average delivery time</h6>
                                    <small class="text-danger fw-normal d-block">
                                        <i class="bx bx-chevron-down"></i>
                                        2.15
                                    </small>
                                </div>
                                <div class="user-progress">
                                    <h6 class="mb-0">2.5 Days</h6>
                                </div>
                            </div>
                        </li>
                        <li class="d-flex">
                            <div class="avatar me-3 flex-shrink-0">
                                <span class="avatar-initial bg-label-danger rounded"><i class="bx bx-group"></i></span>
                            </div>
                            <div class="d-flex w-100 align-items-center justify-content-between flex-wrap gap-2">
                                <div class="me-2">
                                    <h6 class="fw-normal mb-1">Customer satisfaction</h6>
                                    <small class="text-success fw-normal d-block">
                                        <i class="bx bx-chevron-up"></i>
                                        5.7%
                                    </small>
                                </div>
                                <div class="user-progress">
                                    <h6 class="mb-0">4.5/5</h6>
                                </div>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    <div class="row">

        {{-- <div class="col-lg-4 col-md-4 order-1">
            <div class="row">
                <div class="col-lg-6 col-md-12 col-6 mb-4">

                </div>
                <div class="col-lg-6 col-md-12 col-6 mb-4">
                    <div class="card">
                        <div class="card-body">
                            <div class="card-title d-flex align-items-start justify-content-between">
                                <div class="avatar flex-shrink-0">
                                    <img src="{{ asset("assets/img/icons/unicons/wallet-info.png") }}" alt="Credit Card"
                                        class="rounded">
                                </div>
                                <div class="dropdown">
                                    <button class="btn p-0" type="button" id="cardOpt6" data-bs-toggle="dropdown"
                                        aria-haspopup="true" aria-expanded="false">
                                        <i class="bx bx-dots-vertical-rounded"></i>
                                    </button>
                                    <div class="dropdown-menu dropdown-menu-end" aria-labelledby="cardOpt6">
                                        <a class="dropdown-item" href="javascript:void(0);">View More</a>
                                        <a class="dropdown-item" href="javascript:void(0);">Delete</a>
                                    </div>
                                </div>
                            </div>
                            <span>Sales</span>
                            <h3 class="card-title text-nowrap mb-1">$4,679</h3>
                            <small class="text-success fw-medium"><i class='bx bx-up-arrow-alt'></i> +28.42%</small>
                        </div>
                    </div>
                </div>
            </div>
        </div> --}}
        <!-- Total Revenue -->
        <div class="col-12 col-lg-8 order-md-3 order-lg-2 order-2 mb-4">
            <div class="card">
                <div class="row row-bordered g-0">
                    <div class="col-md-8">
                        <h5 class="card-header m-0 me-2 pb-3">Total Revenue</h5>
                        <div id="totalRevenueChart" class="px-2"></div>
                    </div>
                    <div class="col-md-4">
                        <div class="card-body">
                            <div class="text-center">
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-outline-primary dropdown-toggle" type="button"
                                        id="growthReportId" data-bs-toggle="dropdown" aria-haspopup="true"
                                        aria-expanded="false">
                                        2022
                                    </button>
                                    <div class="dropdown-menu dropdown-menu-end" aria-labelledby="growthReportId">
                                        <a class="dropdown-item" href="javascript:void(0);">2021</a>
                                        <a class="dropdown-item" href="javascript:void(0);">2020</a>
                                        <a class="dropdown-item" href="javascript:void(0);">2019</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div id="growthChart"></div>
                        <div class="fw-medium mb-2 pt-3 text-center">62% Company Growth</div>

                        <div class="d-flex px-xxl-4 px-lg-2 gap-xxl-3 gap-lg-1 justify-content-between gap-3 p-4">
                            <div class="d-flex">
                                <div class="me-2">
                                    <span class="badge bg-label-primary p-2"><i
                                            class="bx bx-dollar text-primary"></i></span>
                                </div>
                                <div class="d-flex flex-column">
                                    <small>2022</small>
                                    <h6 class="mb-0">$32.5k</h6>
                                </div>
                            </div>
                            <div class="d-flex">
                                <div class="me-2">
                                    <span class="badge bg-label-info p-2"><i class="bx bx-wallet text-info"></i></span>
                                </div>
                                <div class="d-flex flex-column">
                                    <small>2021</small>
                                    <h6 class="mb-0">$41.2k</h6>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!--/ Total Revenue -->

        <div class="col-12 col-md-8 col-lg-4 order-md-2 order-3">
            <div class="row">
                <div class="col-6 mb-4">
                    <div class="card">
                        <div class="card-body">
                            <div class="card-title d-flex align-items-start justify-content-between">
                                <div class="avatar flex-shrink-0">
                                    <img src="{{ asset("assets/img/icons/unicons/paypal.png") }}" alt="Credit Card"
                                        class="rounded">
                                </div>
                                <div class="dropdown">
                                    <button class="btn p-0" type="button" id="cardOpt4" data-bs-toggle="dropdown"
                                        aria-haspopup="true" aria-expanded="false">
                                        <i class="bx bx-dots-vertical-rounded"></i>
                                    </button>
                                    <div class="dropdown-menu dropdown-menu-end" aria-labelledby="cardOpt4">
                                        <a class="dropdown-item" href="javascript:void(0);">View More</a>
                                        <a class="dropdown-item" href="javascript:void(0);">Delete</a>
                                    </div>
                                </div>
                            </div>
                            <span class="d-block mb-1">Payments</span>
                            <h3 class="card-title mb-2 text-nowrap">$2,456</h3>
                            <small class="text-danger fw-medium"><i class='bx bx-down-arrow-alt'></i> -14.82%</small>
                        </div>
                    </div>
                </div>
                <div class="col-6 mb-4">
                    <div class="card">
                        <div class="card-body">
                            <div class="card-title d-flex align-items-start justify-content-between">
                                <div class="avatar flex-shrink-0">
                                    <img src="{{ asset("assets/img/icons/unicons/cc-primary.png") }}" alt="Credit Card"
                                        class="rounded">
                                </div>
                                <div class="dropdown">
                                    <button class="btn p-0" type="button" id="cardOpt1" data-bs-toggle="dropdown"
                                        aria-haspopup="true" aria-expanded="false">
                                        <i class="bx bx-dots-vertical-rounded"></i>
                                    </button>
                                    <div class="dropdown-menu" aria-labelledby="cardOpt1">
                                        <a class="dropdown-item" href="javascript:void(0);">View More</a>
                                        <a class="dropdown-item" href="javascript:void(0);">Delete</a>
                                    </div>
                                </div>
                            </div>
                            <span class="fw-semibold d-block mb-1">Transactions</span>
                            <h3 class="card-title mb-2">$14,857</h3>
                            <small class="text-success fw-semibold"><i class='bx bx-up-arrow-alt'></i> +28.14%</small>
                        </div>
                    </div>
                </div>
                <!-- </div>
                                                                                                                                                            <div class="row"> -->
                <div class="col-12 mb-4">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between flex-sm-row flex-column gap-3">
                                <div class="d-flex flex-sm-column align-items-start justify-content-between flex-row">
                                    <div class="card-title">
                                        <h5 class="mb-2 text-nowrap">Profile Report</h5>
                                        <span class="badge bg-label-warning rounded-pill">Year 2021</span>
                                    </div>
                                    <div class="mt-sm-auto">
                                        <small class="text-success fw-medium text-nowrap"><i class='bx bx-chevron-up'></i>
                                            68.2%</small>
                                        <h3 class="mb-0">$84,686k</h3>
                                    </div>
                                </div>
                                <div id="profileReportChart"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection
