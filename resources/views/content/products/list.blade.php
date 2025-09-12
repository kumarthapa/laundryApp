@extends("layouts/contentNavbarLayout")

@section("title", " Products")
@section("page-style")
    <link rel="stylesheet" href="{{ asset("assets/css/datatables.bootstrap5.css") }}">
    <style>
    </style>
@endsection
@section("content")
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
                                            {{ isset($productsOverview["total_products"]) ? $productsOverview["total_products"] : "0" }}
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
                                            {{ isset($productsOverview["total_tags"]) ? $productsOverview["total_tags"] : "0" }}
                                        </h3>
                                        <p class="mb-0">Total RFID Tags</p>
                                    </div>
                                    <span class="badge bg-label-warning me-lg-4 rounded p-2">
                                        <i class="bx bx bx-crown bx-sm"></i>
                                    </span>
                                </div>
                                <hr class="d-none d-sm-block d-lg-none">
                            </div>
                            <div class="col-sm-6 col-lg-3">
                                <div
                                    class="d-flex justify-content-between align-items-start border-end pb-sm-0 card-widget-3 pb-3">
                                    <div>
                                        <h3 class="mb-1">
                                            {{ isset($productsOverview["total_pass_products"]) ? $productsOverview["total_pass_products"] : "0" }}
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
                                            {{ isset($productsOverview["total_failed_products"]) ? $productsOverview["total_failed_products"] : "0" }}
                                        </h3>
                                        <p class="mb-0">FAILED Products</p>
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
@section("page-script")
    @include("content.products.modal.bulkProductImport")
    @include("content.partial.datatable")
    @include("content.common.scripts.daterangePicker", [
        "float" => "right",
        "name" => "masterTableDaterangePicker",
    ])
    @include("content.common.scripts.daterangePicker", [
        "float" => "right",
        "name" => "ToursExportDaterangePicker",
        "default_days" => 180, // "0" Means today's record will be show (default is 180 days)
    ])
    <script>
        $(document).ready(function() {
            var tableHeaders = {!! $table_headers !!};
            var options1 = {
                url: "{{ route("products.list") }}",
                createUrl: '{{ route("create.products") }}',
                createPermissions: "{{ isset($createPermissions) ? $createPermissions : "" }}",
                fetchId: "FetchData",
                title: "Planning Products list",
                createTitle: "Manually Create",
                displayLength: 100,
                is_import: "Products Upload",
                importUrl: "{{ route("create.products") }}",
                is_export: "Export",
            };
            var filterData = {
                'qc_status': {
                    'data': {
                        'all': 'ALL',
                        'PASS': 'PASS',
                        'FAILED': 'FAILED',
                        'PENDING': 'PENDING'
                    },
                    'filter_name': 'Filter By Status',
                },
                'current_stage': {
                    'data': {
                        'ALL': 'ALL',
                        'Bonding': 'Bonding',
                        'Tapedge': 'Tapedge',
                        'Zip Cover': 'Zip Cover',
                        'QC': 'QC',
                        'Packing': 'Packing',
                        'Ready for Shipment': 'Ready for Shipment',
                        'Shipped': 'Shipped',
                        'Returned': 'Returned',
                        'Cancelled': 'Cancelled',
                    },
                    'filter_name': 'Filter By Stage',
                },
            };


            console.log(filterData)
            getDataTableS(options1, filterData, tableHeaders, getStats);

            function getStats(params) {
                console.log("status;", params);
            }
            $(".addNewRecordBtn").click(function() {
                window.location.href = '{{ route("create.products") }}';
            })

            let exportUrl = "{{ route("products.exportProducts") }}";
            $(".exportBtn").click(function() {
                window.location.href = exportUrl;
            });
            $(".bulkImportBtn").click(function() {
                $("#bulkProductImportModal").modal('show');
            })
        });
        //Delete row
        function deleteRow(url) {
            if (!url) {
                alert('Permission denied!');
                return false;
            }
            console.log("DELET URL: ", url)
            // Display a confirmation dialog to the user
            if (!confirm("Are you sure you want to delete this item?")) {
                return false;
            }
            $.ajax({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                url: url, // URL for the AJAX call
                method: 'POST',
                success: function(response) {
                    console.log(response)
                    toastr.success(response.message);
                    if (response.success) {
                        window.location.reload();
                    }
                },
                error: function(xhr, status, error) {
                    toastr.error(response.message, error);
                }
            });
            // call your ajax here
        }
    </script>

@endsection
