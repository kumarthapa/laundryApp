@extends('layouts/contentNavbarLayout')

@section('title', ' Reports')
@section('page-style')
    <link rel="stylesheet" href="{{ asset('assets/css/datatables.bootstrap5.css') }}">
    <style>
        .form-control-sm {
            padding: 0px !important;
        }

        #globalLoader {
            position: fixed;
            inset: 0;
            z-index: 1055;
            /* above modals */
        }

        #globalLoader .loader-backdrop {
            position: absolute;
            inset: 0;
            background: rgba(255, 255, 255, 0.75);
            backdrop-filter: blur(3px);
        }

        #globalLoader .loader-content {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
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
        <div class="col-lg-3 col-md-4 col-sm-6 col-12 mb-3">
            <div class="card card-border-shadow-primary h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="avatar me-4">
                            <span class="avatar-initial bg-label-primary rounded"><i
                                    class="icon-base bx bx-archive icon-lg"></i></span>
                        </div>
                        <h5 class="mb-0">Daily Floor Products</h5>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div>
                            <p class="mb-0">
                                <span class="text-heading fw-medium me-2">Today's</span>
                                <span class="text-body-secondary">Total Overall floor products</span>
                            </p>
                        </div>
                        <button type="button" onclick="commonGenerateReports('daily_floor_stock_report')"
                            class="btn btn-label-primary">Generate</button>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-4 col-sm-6 col-12 mb-3">
            <div class="card card-border-shadow-warning h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="avatar me-4">
                            <span class="avatar-initial bg-label-warning rounded"><i
                                    class="icon-base bx bx-store-alt icon-lg"></i></span>
                        </div>
                        <h5 class="mb-0">Monthly and Yearly Reports</h5>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div>
                            <p class="mb-0">
                                <span class="text-heading fw-medium me-2">Overall</span>
                                <span class="text-body-secondary">Overall Total products</span>
                            </p>
                        </div>
                        <button type="button" onclick="commonGenerateReports('monthly_yearly_report')"
                            class="btn btn-label-primary">Generate</button>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-4 col-sm-6 col-12 mb-3">
            <div class="card card-border-shadow-danger h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="avatar me-4">
                            <span class="avatar-initial bg-label-danger rounded"><i
                                    class="icon-base bx bx-package icon-lg"></i></span>
                        </div>
                        <h5 class="mb-0">Daily Packing Report</h5>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div>
                            <p class="mb-0">
                                <span class="text-heading fw-medium me-2">Today's</span>
                                <span class="text-body-secondary">Total packing products</span>
                            </p>
                        </div>
                        <button type="button" onclick="commonGenerateReports('daily_packing_report')"
                            class="btn btn-label-primary">Generate</button>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-4 col-sm-6 col-12 mb-3">
            <div class="card card-border-shadow-info h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="avatar me-4">
                            <span class="avatar-initial bg-label-info rounded"><i
                                    class="icon-base bx bx-time-five icon-lg"></i></span>
                        </div>
                        <h5 class="mb-0">Daily Tapedge Report</h5>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div>
                            <p class="mb-0">
                                <span class="text-heading fw-medium me-2">Today's</span>
                                <span class="text-body-secondary">Total tapedge products</span>
                            </p>
                        </div>
                        <button type="button" onclick="commonGenerateReports('daily_tapedge_report')"
                            class="btn btn-label-primary">Generate</button>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-4 col-sm-6 col-12 mb-3">
            <div class="card card-border-shadow-danger h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="avatar me-4">
                            <span class="avatar-initial bg-label-danger rounded"><i
                                    class="icon-base bx bx-git-repo-forked icon-lg"></i></span>
                        </div>
                        <h5 class="mb-0">Daily Zip Cover Report</h4>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div>
                            <p class="mb-0">
                                <span class="text-heading fw-medium me-2">Today's</span>
                                <span class="text-body-secondary">Total zip cover products</span>
                            </p>
                        </div>
                        <button type="button" onclick="commonGenerateReports('daily_zip_cover_report')"
                            class="btn btn-label-primary">Generate</button>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-4 col-sm-6 col-12 mb-3">
            <div class="card card-border-shadow-primary h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="avatar me-4">
                            <span class="avatar-initial bg-label-primary rounded"><i
                                    class="icon-base bx bx-store-alt icon-lg"></i></span>
                        </div>
                        <h5 class="mb-0">Daily Bonding Report</h5>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div>
                            <p class="mb-0">
                                <span class="text-heading fw-medium me-2">Today's</span>
                                <span class="text-body-secondary">Pass bonding products</span>
                            </p>
                        </div>
                        <button type="button" onclick="commonGenerateReports('daily_bonding_report')"
                            class="btn btn-label-primary">Generate</button>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-4 col-sm-6 col-12 mb-3">
            <div class="card card-border-shadow-primary h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="avatar me-4">
                            <span class="avatar-initial bg-label-primary rounded"><i
                                    class="icon-base bx bx-store-alt icon-lg"></i></span>
                        </div>
                        <h5 class="mb-0">All Bonding Products</h5>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div>
                            <p class="mb-0">
                                <span class="text-heading fw-medium me-2">Overall</span>
                                <span class="text-body-secondary">All pass bonding products</span>
                            </p>
                        </div>
                        <button type="button" onclick="commonGenerateReports('all_bonding_report')"
                            class="btn btn-label-primary">Generate</button>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-4 col-sm-6 col-12 mb-3">
            <div class="card card-border-shadow-primary h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="avatar me-4">
                            <span class="avatar-initial bg-label-primary rounded"><i
                                    class="icon-base bx bx-store-alt icon-lg"></i></span>
                        </div>
                        <h5 class="mb-0">Floor Stock Bonding</h5>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div>
                            <p class="mb-0">
                                <span class="text-heading fw-medium me-2">Overall</span>
                                <span class="text-body-secondary">Ready to next QC</span>
                            </p>
                        </div>
                        <button type="button" onclick="commonGenerateReports('floor_stock_bonding')"
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
                        <div class="col-md-3 mb-md-0 mb-3">
                            <select id="filterQcStatus" class="form-select filter-selected-data">
                                <option value="">All QC Status</option>
                                @if (isset($stages) && $stages)
                                    @foreach ($status as $statusArray)
                                        <option value="{{ $statusArray['value'] }}">{{ $statusArray['name'] }}</option>
                                    @endforeach
                                @endif
                            </select>
                        </div>
                        <div class="col-md-3 mb-md-0 mb-3">
                            <select id="filterCurrentStage" class="form-select filter-selected-data">
                                <option value="">All Current Stages</option>
                                @if (isset($stages) && $stages)
                                    @foreach ($stages as $arrays)
                                        <option value="{{ $arrays['value'] }}">{{ $arrays['name'] }}</option>
                                    @endforeach
                                @endif
                            </select>
                        </div>
                        <div class="col-md-3 mb-md-0 mb-3">
                            <div class="input-group date">
                                <input class="form-control filter-selected-data" type="text"
                                    name="masterTableDaterangePicker" placeholder="DD/MM/YY" id="selectedDaterange" />
                                <span class="input-group-text">
                                    <i class='bx bxs-calendar'></i>
                                </span>
                            </div>
                        </div>
                        <!-- Download Button -->
                        <div class="col-md-3 d-flex justify-content-end mb-md-0 mb-3" id="downloadButtonsDiv">
                            <button class="btn btn-sm btn-primary d-flex align-items-center w-auto me-2"
                                onclick="downloadSeletedReport('daily_floor_stock_report')">
                                <i class="icon-base bx bx-export icon-sm me-1"></i>
                                <span>Export All</span>
                                {{-- <span class="d-md-none d-sm-inline">Export All</span> --}}
                            </button>
                            <button class="btn btn-sm btn-primary d-flex align-items-center w-auto"
                                onclick="downloadDefectReport()">
                                <i class="icon-base bx bx-export icon-sm me-1"></i>
                                <span>Defect report</span>
                                {{-- <span class="d-md-none d-sm-inline">Defect report</span> --}}
                            </button>
                        </div>
                        {{-- <div class="col-md-2 d-flex justify-content-md-end">
                            <button class="btn btn-sm btn-primary d-flex align-items-center w-auto"
                                onclick="downloadSeletedReport('daily_floor_stock_report')">
                                <i class="icon-base bx bx-export icon-sm me-1"></i>
                                <span class="d-sm-none d-md-inline">Download report</span>
                                <span class="d-md-none d-sm-inline">Export</span>
                            </button>
                        </div> --}}
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
    <!-- Global Full-Screen Loader -->
    <div id="globalLoader" class="d-none">
        <div class="loader-backdrop"></div>
        <div class="loader-content text-center">
            <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-3 text-muted fw-semibold">Generating report, please wait...</p>
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
        /* Reports page - corrected DataTable initialization and centered Total Records card */
        let currentReportType = 'daily_floor_stock_report'; // default report on page load
        $(document).ready(function() {
            let isResettingFilters = false;
            $("#filterQcStatus").select2({
                allowClear: true
            });
            $("#filterCurrentStage").select2({
                allowClear: true
            });

            // Load default report data initially
            commonGenerateReports(currentReportType);

            // Reload data on filter change
            $('#selectedDaterange, #filterQcStatus, #filterCurrentStage').change(function() {
                if (isResettingFilters) return; // ⛔ skip if resetting
                if (currentReportType) {
                    commonGenerateReports(currentReportType);
                }
            });
        });

        function initializeDataTable(data, columns) {
            // Destroy existing instance if present
            if ($.fn.DataTable.isDataTable('#DataTables2025')) {
                try {
                    $('#DataTables2025').DataTable().clear().destroy();
                } catch (e) {
                    // ignore
                }
                $('#DataTables2025').empty();
            }

            // Create table
            var table = $('#DataTables2025').DataTable({
                data: data || [],
                columns: columns || [],
                responsive: true,
                processing: true,
                serverSide: false,
                displayLength: 30,
                lengthMenu: [7, 10, 25, 50, 75, 100],
                // pageLength: 30,
                // remove default "Search:" label and set placeholder
                language: {
                    search: "",
                    searchPlaceholder: "Search ...",
                    emptyTable: "No data available for this report"
                },
                // place controls exactly like your other datatables if needed; using default dom is fine here
                dom: '<"card-header"<"head-label text-center">><"d-flex justify-content-between align-items-center row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>t<"d-flex justify-content-between row"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',
                initComplete: function() {
                    // tidy up search input label text and classes
                    var $filter = $('#DataTables2025_filter');
                    var $label = $filter.find('label');

                    // remove plain text nodes inside label (the "Search:" text)
                    $label.contents().filter(function() {
                        return this.nodeType === 3; // text node
                    }).remove();

                    var $input = $filter.find('input[type="search"]');
                    $input.removeClass('form-control-sm');
                    if (!$input.hasClass('form-control')) $input.addClass('form-control');
                    if (!$input.attr('placeholder') || $input.attr('placeholder') === '') {
                        $input.attr('placeholder', 'search ...');
                    }

                    // create centered Total Records card once
                    tryCreateCenterTotalCard(this.api());
                    // set initial total (use server-provided data length or client-side count)
                    const initialTotal = this.api().rows({
                        search: 'applied'
                    }).count();
                    $('#totalRowsCard').text('Total Records: ' + initialTotal);
                },
                drawCallback: function(settings) {
                    // ensure the search input retains correct classes (DataTables can recreate it)
                    var $filterInput = $('#DataTables2025_filter input[type="search"]');
                    if ($filterInput.length) {
                        $filterInput.removeClass('form-control-sm');
                        if (!$filterInput.attr('placeholder') || $filterInput.attr('placeholder') === '') {
                            $filterInput.attr('placeholder', 'search ...');
                        }
                    }

                    // update the center total count each draw using applied search filter
                    const api = this.api();
                    const total = api.rows({
                        search: 'applied'
                    }).count();
                    $('#totalRowsCard').text('Total Records: ' + total);
                }
            });

            // helper: create center Total Rows card (idempotent)
            function tryCreateCenterTotalCard(api) {
                // Find the info container row generated by DataTables
                const $info = $('#DataTables2025_info');
                if (!$info.length) {
                    // info not present yet — schedule a short retry after next tick
                    setTimeout(function() {
                        if (!$('#totalRowsCard').length) tryCreateCenterTotalCard(api);
                    }, 30);
                    return;
                }

                const wrapperRow = $info.closest('.row');
                if (!wrapperRow.length) return;

                // If total card already exists, ensure layout classes are correct
                if ($('#totalRowsCard').length) {
                    // nothing else to do
                    return;
                }

                // Adjust left & right columns to col-md-4 and insert center column
                const colInfo = wrapperRow.find('.col-md-6').first();
                const colPaginate = wrapperRow.find('.col-md-6').last();

                if (colInfo.length && colPaginate.length) {
                    colInfo.removeClass('col-md-6').addClass('col-md-4');
                    colPaginate.removeClass('col-md-6').addClass('col-md-4');

                    // Insert center column just before paginate column
                    colPaginate.before(`
                <div class="col-sm-12 col-md-4 text-center">
                    <div id="totalRowsCard" class="card fw-semibold bg-label-primary text-light py-2">Total Records: 0</div>
                </div>
            `);
                } else {
                    // fallback: append center card under the table info area
                    if (!$('#totalRowsCard').length) {
                        $info.after(`
                    <div class="mt-2 text-center">
                        <div id="totalRowsCard" class="fw-semibold text-primary">Total Records: 0</div>
                    </div>
                `);
                    }
                }
            }

            // expose the DataTable instance if caller needs it
            return table;
        }

        const reportTitles = {
            'daily_floor_stock_report': 'Daily Floor Stock Report',
            'monthly_yearly_report': 'Monthly and Yearly Reports',
            'daily_packing_report': 'Daily Packing Report',
            'daily_tapedge_report': 'Daily Tapedge Report',
            'daily_zip_cover_report': 'Daily Zip Cover Report',
            'daily_bonding_report': 'Daily Bonding Report',
            'all_bonding_report': 'All Bonding Products Report',
            'floor_stock_bonding': 'Floor Stock Bonding Report',
        };

        function commonGenerateReports(reportType) {
            if (!reportType) {
                alert("Report Not Found!!");
                return;
            }

            currentReportType = reportType || currentReportType;
            if (currentReportType === 'daily_floor_stock_report' || currentReportType === 'monthly_yearly_report') {

                $('#filterQcStatus').parent().show();
                $('#filterCurrentStage').parent().show();
                $('#downloadButtonsDiv').addClass('col-md-3').removeClass('col-md-9');

            } else {

                isResettingFilters = true; // prevent infinite loop

                // Clear Select2 values safely
                $('#filterQcStatus').val(null).trigger('change.select2');
                $('#filterCurrentStage').val(null).trigger('change.select2');

                isResettingFilters = false; // restore normal behavior

                $('#filterQcStatus').parent().hide();
                $('#filterCurrentStage').parent().hide();
                $('#downloadButtonsDiv').addClass('col-md-9').removeClass('col-md-3');
            }

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
                beforeSend: function() {
                    $('#globalLoader').removeClass('d-none');
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

                    // Defensive checks for response shape
                    if (!response || typeof response.data === 'undefined' || typeof response.columns ===
                        'undefined') {
                        console.error('Invalid report response format', response);
                        initializeDataTable([], []);
                        return;
                    }

                    // Initialize DataTable with returned data & columns
                    initializeDataTable(response.data, response.columns);
                },
                error: function(xhr, status, err) {
                    console.error('Failed to fetch report data', status, err, xhr.responseText);
                    alert('Failed to fetch report data.');
                },
                complete: function() {
                    $('#globalLoader').addClass('d-none');
                }
            });
        }

        function downloadSeletedReport(reportType) {
            if (!reportType) {
                alert("Report Not Found!!");
                return;
            }
            // create a temporary form and submit POST to trigger file download
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = "{{ route('reports.export') }}";
            form.style.display = 'none';

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

        function downloadDefectReport() {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = "{{ route('reports.defect_export') }}";
            form.style.display = 'none';

            const tokenInput = document.createElement('input');
            tokenInput.type = 'hidden';
            tokenInput.name = '_token';
            tokenInput.value = "{{ csrf_token() }}";
            form.appendChild(tokenInput);

            const inputs = {
                selectedDaterange: document.getElementById('selectedDaterange') ? document.getElementById(
                    'selectedDaterange').value : '',
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
