@extends("layouts/contentNavbarLayout")

@section("title", " Reports")
@section("page-style")
    <link rel="stylesheet" href="{{ asset("assets/css/datatables.bootstrap5.css") }}">
    <style>
        .form-control-sm {
            padding: 0px !important;
        }
    </style>
@endsection
@section("content")
    <div class="row g-6">
        <!-- Card Border Shadow -->
        <div class="col-lg-4 col-sm-8 mb-3">
            <div class="card card-border-shadow-primary h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="avatar me-4">
                            <span class="avatar-initial bg-label-primary rounded"><i
                                    class="icon-base bx bx-store-alt icon-lg"></i></span>
                        </div>
                        <h4 class="mb-0">Daily Floor Stock Report</h4>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div>
                            <p class="mb-0">
                                <span class="text-heading fw-medium me-2">+18.2%</span>
                                <span class="text-body-secondary">than last week</span>
                            </p>
                        </div>
                        <button type="button" onclick="commonGenerateReports('stock_report')"
                            class="btn btn-label-primary">Generate</button>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-sm-8 mb-3">
            <div class="card card-border-shadow-warning h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="avatar me-4">
                            <span class="avatar-initial bg-label-warning rounded"><i
                                    class="icon-base bx bx-archive icon-lg"></i></span>
                        </div>
                        <h4 class="mb-0">Monthly and Yearly Reports</h4>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div>
                            <p class="mb-0">
                                <span class="text-heading fw-medium me-2">+18.2%</span>
                                <span class="text-body-secondary">than last week</span>
                            </p>
                        </div>
                        <button type="button" onclick="commonGenerateReports()"
                            class="btn btn-label-primary">Generate</button>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-sm-8 mb-3">
            <div class="card card-border-shadow-danger h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="avatar me-4">
                            <span class="avatar-initial bg-label-danger rounded"><i
                                    class="icon-base bx bx-package icon-lg"></i></span>
                        </div>
                        <h4 class="mb-0">Daily Packing Report</h4>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div>
                            <p class="mb-0">
                                <span class="text-heading fw-medium me-2">+18.2%</span>
                                <span class="text-body-secondary">than last week</span>
                            </p>
                        </div>
                        <button type="button" onclick="commonGenerateReports()"
                            class="btn btn-label-primary">Generate</button>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-sm-8 mb-3">
            <div class="card card-border-shadow-info h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="avatar me-4">
                            <span class="avatar-initial bg-label-info rounded"><i
                                    class="icon-base bx bx-time-five icon-lg"></i></span>
                        </div>
                        <h4 class="mb-0">Daily Tapedge Report</h4>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div>
                            <p class="mb-0">
                                <span class="text-heading fw-medium me-2">+18.2%</span>
                                <span class="text-body-secondary">than last week</span>
                            </p>
                        </div>
                        <button type="button" onclick="commonGenerateReports()"
                            class="btn btn-label-primary">Generate</button>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-sm-8 mb-3">
            <div class="card card-border-shadow-danger h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="avatar me-4">
                            <span class="avatar-initial bg-label-danger rounded"><i
                                    class="icon-base bx bx-git-repo-forked icon-lg"></i></span>
                        </div>
                        <h4 class="mb-0">Daily Zip Cover Report</h4>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div>
                            <p class="mb-0">
                                <span class="text-heading fw-medium me-2">+18.2%</span>
                                <span class="text-body-secondary">than last week</span>
                            </p>
                        </div>
                        <button type="button" onclick="commonGenerateReports()"
                            class="btn btn-label-primary">Generate</button>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-sm-8 mb-3">
            <div class="card card-border-shadow-primary h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="avatar me-4">
                            <span class="avatar-initial bg-label-primary rounded"><i
                                    class="icon-base bx bx-archive icon-lg"></i></span>
                        </div>
                        <h4 class="mb-0">Daily Bonding Report</h4>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div>
                            <p class="mb-0">
                                <span class="text-heading fw-medium me-2">+18.2%</span>
                                <span class="text-body-secondary">than last week</span>
                            </p>
                        </div>
                        <button type="button" onclick="commonGenerateReports('daily_bonding_report')"
                            class="btn btn-label-primary">Generate</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <!--- Filters ------------ START ---------------->
                <div class="card-header border-bottom">
                    <h5 class="mb-1" id="reportName"><i class="bx bx-food-menu"></i>Daily Floor Stock Report</h5>
                    <div class="d-flex mb-0"> Date Range:&nbsp;
                        <p id="filterDatetime">Aug 17, 2020, 5:48 (ET)</p>
                    </div>

                    {{-- <h6>Search By Filters</h6> --}}

                    <div class="row pt-3" id="filter-container">
                        <div class="col-md-4">
                            <select id="filterQcStatus" class="form-select filter-selected-data">
                                <option value="">All QC Status</option>
                                <option value="PASS">PASS</option>
                                <option value="FAILED">FAILED</option>
                                <option value="PENDING">PENDING</option>
                            </select>

                        </div>
                        <div class="col-md-8">
                            <div class="input-group date">
                                <input class="form-control filter-selected-data" type="text"
                                    name="masterTableDaterangePicker" placeholder="DD/MM/YY" id="selectedDaterange" />
                                <span class="input-group-text">
                                    <i class='bx bxs-calendar'></i>
                                </span>
                            </div>
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
@php
    $is_export = 1;
@endphp
@section("page-script")
    {{-- @include("content.reports.reportsDatatable") --}}
    @include("content.common.scripts.daterangePicker", [
        "float" => "right",
        "name" => "masterTableDaterangePicker",
        "default_days" => 0,
    ])
    <script>
        let currentReportType = 'stock_report'; // default report on page load
        $(document).ready(function() {
            $("#filterQcStatus").select2({
                allowClear: true
            })
            // Load default report data initially
            commonGenerateReports(currentReportType);

            // Reload data on filter change
            $('#selectedDaterange, #filterStatus').change(function() {
                if (currentReportType) {
                    commonGenerateReports(currentReportType);
                }
            });
        });

        function initializeDataTable(data, columns) {
            if ($.fn.DataTable.isDataTable('#DataTables2025')) {
                let table = $('#DataTables2025').DataTable();
                table.clear().destroy(); // clear and destroy
                $('#DataTables2025').empty(); // remove old table elements
            }
            $('#DataTables2025').DataTable({
                data: data,
                columns: columns,
                responsive: true,
                processing: true,
                serverSide: false,
                lengthMenu: [7, 10, 25, 50, 75, 100],
                pageLength: 10,
                language: {
                    emptyTable: "No data available for this report"
                }
            });
        }


        const reportTitles = {
            'stock_report': 'Daily Floor Stock Report',
            'monthly_yearly_report': 'Monthly and Yearly Reports',
            'daily_packing_report': 'Daily Packing Report',
            'daily_tapedge_report': 'Daily Tapedge Report',
            'daily_zip_cover_report': 'Daily Zip Cover Report',
            'daily_bonding_report': 'Daily Bonding Report',
        };

        function commonGenerateReports(reportType) {
            if (!reportType) {
                alert("Report Not Found!!");
            }
            currentReportType = reportType || currentReportType;

            $.ajax({
                url: "{{ route("reports.list") }}",
                method: "POST",
                data: {
                    _token: '{{ csrf_token() }}',
                    report_type: currentReportType,
                    selectedDaterange: $('#selectedDaterange').val(),
                    status: $('#filterStatus').val()
                },
                success: function(response) {
                    $('#reportName').html('<i class="bx bx-food-menu"></i> ' + (reportTitles[
                        currentReportType] || 'Report'));

                    let selectedDate = $('#selectedDaterange').val();
                    if (selectedDate) {
                        $('#filterDatetime').text(selectedDate);
                    } else {
                        let now = new Date();
                        let options = {
                            year: 'numeric',
                            month: 'short',
                            day: 'numeric',
                            hour: '2-digit',
                            minute: '2-digit',
                            timeZoneName: 'short'
                        };
                        $('#filterDatetime').text(now.toLocaleString('en-US', options));

                    }
                    console.log(response.columns)
                    initializeDataTable(response.data, response.columns);
                },
                error: function() {
                    alert('Failed to fetch report data.');
                }
            });
        }
    </script>
@endsection
