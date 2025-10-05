@extends('layouts/contentNavbarLayout')

@section('title', ' Reports')
@section('page-style')
    <link rel="stylesheet" href="{{ asset('assets/css/datatables.bootstrap5.css') }}">
    <style>
        .form-control-sm {
            padding: 0px !important;
        }
    </style>
@endsection
@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="d-none mb-3" id="errorBox"></div>
            <div class="card mb-4">
                <div class="card-widget-separator-wrapper">
                    <div class="card-body card-widget-separator">
                        <div class="row gy-4 gy-sm-1">
                            <div class="col-sm-6 col-lg-3">
                                <div
                                    class="d-flex justify-content-between align-items-start card-widget-1 border-end pb-sm-0 pb-3">
                                    <div>
                                        <h5 class="mb-1">
                                            {{ $productsOverview['total_products'] ?? '0' }}
                                        </h5>
                                        <p class="mb-0">Total Products</p>
                                    </div>
                                    <span class="badge bg-label-success me-sm-4 rounded p-2">
                                        <i class="bx bx-store-alt bx-sm"></i>
                                    </span>
                                </div>
                                <hr class="d-none d-sm-block d-lg-none me-4">
                            </div>
                            <div class="col-sm-6 col-lg-3">
                                <div
                                    class="d-flex justify-content-between align-items-start card-widget-2 border-end pb-sm-0 pb-3">
                                    <div>
                                        <h5 class="mb-1">
                                            {{ $productsOverview['total_qa_code'] ?? '0' }}
                                        </h5>
                                        <p class="mb-0">Total QA Code</p>
                                    </div>
                                    <span class="badge bg-label-warning me-lg-4 rounded p-2">
                                        <i class="bx bx-crown bx-sm"></i>
                                    </span>
                                </div>
                                <hr class="d-none d-sm-block d-lg-none">
                            </div>
                            <div class="col-sm-6 col-lg-3">
                                <div
                                    class="d-flex justify-content-between align-items-start border-end pb-sm-0 card-widget-3 pb-3">
                                    <div>
                                        <h5 class="mb-1">
                                            {{ $productsOverview['total_pass_products'] ?? '0' }}
                                        </h5>
                                        <p class="mb-0">PASS Products</p>
                                    </div>
                                    <span class="badge bg-label-success me-sm-4 rounded p-2">
                                        <i class="bx bx-check-circle bx-sm"></i>
                                    </span>
                                </div>
                            </div>
                            <div class="col-sm-6 col-lg-3">
                                <div
                                    class="d-flex justify-content-between align-items-start border-end pb-sm-0 card-widget-3 pb-3">
                                    <div>
                                        <h5 class="mb-1">
                                            {{ $productsOverview['total_fail_products'] ?? '0' }}
                                        </h5>
                                        <p class="mb-0">FAIL Products</p>
                                    </div>
                                    <span class="badge bg-label-danger me-sm-4 rounded p-2">
                                        <i class="bx bx-error bx-sm"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
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
                        <button type="button" onclick="commonGenerateReports('daily_floor_stock_report')"
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
                        <button type="button" onclick="commonGenerateReports('monthly_yearly_report')"
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
                        <button type="button" onclick="commonGenerateReports('daily_packing_report')"
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
                        <button type="button" onclick="commonGenerateReports('daily_tapedge_report')"
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
                        <button type="button" onclick="commonGenerateReports('daily_zip_cover_report')"
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
                        <div class="col-md-3">
                            <select id="filterQcStatus" class="form-select filter-selected-data">
                                <option value="">All QC Status</option>
                                @if (isset($stages) && $stages)
                                    @foreach ($status as $statusArray)
                                        <option value="{{ $statusArray['value'] }}">{{ $statusArray['name'] }}</option>
                                    @endforeach
                                @endif
                            </select>

                        </div>
                        <div class="col-md-3">
                            <select id="filterCurrentStage" class="form-select filter-selected-data">
                                <option value="">All Current Stages</option>
                                @if (isset($stages) && $stages)
                                    @foreach ($stages as $arrays)
                                        <option value="{{ $arrays['value'] }}">{{ $arrays['name'] }}</option>
                                    @endforeach
                                @endif
                            </select>
                        </div>
                        <div class="col-md-4">
                            <div class="input-group date">
                                <input class="form-control filter-selected-data" type="text"
                                    name="masterTableDaterangePicker" placeholder="DD/MM/YY" id="selectedDaterange" />
                                <span class="input-group-text">
                                    <i class='bx bxs-calendar'></i>
                                </span>
                            </div>
                        </div>
                        <!-- Download Button -->
                        <div class="col-md-2 d-flex justify-content-md-end">
                            <button class="btn btn-sm btn-primary d-flex align-items-center w-auto"
                                onclick="downloadSeletedReport('daily_floor_stock_report')">
                                <i class="icon-base bx bx-export icon-sm me-1"></i>
                                <span class="d-sm-none d-md-inline">Download report</span>
                                <span class="d-md-none d-sm-inline">Export</span>
                            </button>
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
@section('page-script')
    {{-- @include("content.reports.reportsDatatable") --}}
    @include('content.common.scripts.daterangePicker', [
        'float' => 'right',
        'name' => 'masterTableDaterangePicker',
        'default_days' => 0,
    ])
    <script>
        let currentReportType = 'daily_floor_stock_report'; // default report on page load
        $(document).ready(function() {
            $("#filterQcStatus").select2({
                allowClear: true
            })
            $("#filterCurrentStage").select2({
                allowClear: true
            })
            // Load default report data initially
            commonGenerateReports(currentReportType);

            // Reload data on filter change
            $('#selectedDaterange, #filterQcStatus, #filterCurrentStage').change(function() {
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
            'daily_floor_stock_report': 'Daily Floor Stock Report',
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
                url: "{{ route('reports.list') }}",
                method: "POST",
                data: {
                    _token: '{{ csrf_token() }}',
                    report_type: currentReportType,
                    selectedDaterange: $('#selectedDaterange').val(),
                    status: $('#filterQcStatus').val() || '',
                    stage: $('#filterCurrentStage').val() || ''
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


        function downloadSeletedReport(reportType) {
            if (!reportType) {
                alert("Report Not Found!!");
            }
            // create a temporary form and submit POST to trigger file download
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = "{{ route('reports.export') }}";
            form.style.display = 'none';
            form.target = '_blank'; // open in new tab/window so file download starts

            // csrf
            const tokenInput = document.createElement('input');
            tokenInput.type = 'hidden';
            tokenInput.name = '_token';
            tokenInput.value = "{{ csrf_token() }}";
            form.appendChild(tokenInput);

            reportType = currentReportType || reportType;


            // required values
            const inputs = {
                report_type: reportType,
                selectedDaterange: document.getElementById('selectedDaterange') ? document.getElementById(
                    'selectedDaterange').value : '',
                status: $('#filterQcStatus').length ? $('#filterQcStatus').val() : '',
                stage: $('#filterCurrentStage').length ? $('#filterCurrentStage').val() : ''
            };

            for (const name in inputs) {
                const el = document.createElement('input');
                el.type = 'hidden';
                el.name = name;
                el.value = inputs[name] ?? '';
                form.appendChild(el);
            }

            document.body.appendChild(form);
            form.submit();
            document.body.removeChild(form);
        }
    </script>
@endsection
