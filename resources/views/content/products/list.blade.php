@extends('layouts/contentNavbarLayout')

@section('title', 'Products List')
@section('page-style')
    <link rel="stylesheet" href="{{ asset('assets/css/datatables.bootstrap5.css') }}">
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
                                        <h3 class="mb-1">
                                            {{ $productsOverview['total_products'] ?? '0' }}
                                        </h3>
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
                                        <h3 class="mb-1">
                                            {{ $productsOverview['total_qa_code'] ?? '0' }}
                                        </h3>
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
                                        <h3 class="mb-1">
                                            {{ $productsOverview['total_pass_products'] ?? '0' }}
                                        </h3>
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
                                        <h3 class="mb-1">
                                            {{ $productsOverview['total_fail_products'] ?? '0' }}
                                        </h3>
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

    <div class="row">
        <div class="col-12">
            <div class="card">
                <!--- Filters ------------ START ---------------->
                <div class="card-header border-bottom">
                    <h6>Search By Filters</h6>
                    <div class="d-flex justify-content-between align-items-center gap-3 pt-3" id="filter-container">
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
                    <table class="datatables-basic border-top table" id="DataTables2024">
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
    @include('content.products.modal.bulkProductImport')
    @include('content.partial.datatable')
    @include('content.common.scripts.daterangePicker', [
        'float' => 'right',
        'name' => 'masterTableDaterangePicker',
        'default_days' => 0,
    ])
    <script>
        $(document).ready(function() {
            var tableHeaders = {!! $table_headers !!};
            var options1 = {
                url: "{{ route('products.list') }}",
                createUrl: '{{ route('create.products') }}',
                createPermissions: "{{ $createPermissions ?? '' }}",
                fetchId: "FetchData",
                title: "Products List",
                createTitle: "Manually Create",
                displayLength: 100,
                // is_import: "Upload Products",
                is_delete: "{{ $deletePermissions ?? '' }}",
                delete_url: "{{ route('delete.products') }}",
                importUrl: "{{ route('create.products') }}",
                is_export: "Export All",
                is_export2: "Export Stage Wise",
                manuall_create: false,
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

            $(".addNewRecordBtn").click(function() {
                window.location.href = '{{ route('create.products') }}';
            });

            let exportUrl = "{{ route('products.exportProducts') }}";
            $(".exportBtn").click(function() {
                let selectedDaterange = document.getElementById('selectedDaterange').value || '';
                if (selectedDaterange) {
                    window.location.href =
                        `${exportUrl}?daterange=${encodeURIComponent(selectedDaterange)}`;
                } else {
                    window.location.href = exportUrl;
                }

            });
            let exportUrl2 = "{{ route('products.exportProductsStageWise') }}";
            $(".exportBtn2").click(function() {
                let selectedDaterange = document.getElementById('selectedDaterange').value || '';
                if (selectedDaterange) {
                    window.location.href =
                        `${exportUrl2}?daterange=${encodeURIComponent(selectedDaterange)}`;
                } else {
                    window.location.href = exportUrl2;
                }
            });

            $(".bulkImportBtn").click(function() {
                $("#bulkProductImportModal").modal('show');
            });
        });

        // Delete row
        function deleteRow(url) {
            if (!url) {
                alert('Permission denied!');
                return false;
            }
            if (!confirm("Are you sure you want to delete this item?")) {
                return false;
            }
            $.ajax({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                url: url,
                method: 'POST',
                success: function(response) {
                    toastr.success(response.message);
                    if (response.success) {
                        window.location.reload();
                    }
                },
                error: function(xhr, status, error) {
                    toastr.error("Error: " + error);
                }
            });
        }
    </script>



@endsection
