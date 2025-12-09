@extends('layouts/contentNavbarLayout')

@section('title', 'Inventory Management')
@section('page-style')
    <link rel="stylesheet" href="{{ asset('assets/css/datatables.bootstrap5.css') }}">
    <style>
        @media screen and (max-width: 768px) {
            .select2-container--default {
                margin-bottom: 10px !important;
            }

            .create-new {
                margin-bottom: 10px !important;
            }
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
                                        <h3 class="mb-1">
                                            {{ $inventoryOverview['total_inventory'] ?? '0' }}
                                        </h3>
                                        <p class="mb-0">Total Inventory</p>
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
                                            {{ $inventoryOverview['total_new'] ?? '0' }}
                                        </h3>
                                        <p class="mb-0">Total New</p>
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
                                            {{ $inventoryOverview['total_clean'] ?? '0' }}
                                        </h3>
                                        <p class="mb-0">Total Clean</p>
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
                                            {{ $inventoryOverview['total_dirty'] ?? '0' }}
                                        </h3>
                                        <p class="mb-0">Total Dirty </p>
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
                {{-- <div class="card-header border-bottom">
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
                </div> --}}
                {{-- // Use later --}}
                {{-- <div class="card-header border-bottom">
                    <h6>Search By Filters</h6>
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
    @include('content.inventory.modal.bulkBondingProductImport')
    @include('content.partial.datatable')
    @include('content.common.scripts.daterangePicker', [
        'float' => 'right',
        'name' => 'masterTableDaterangePicker',
        'default_days' => 30,
    ])
    <script>
        $(document).ready(function() {
            var tableHeaders = {!! $table_headers !!};
            var options1 = {
                url: "{{ route('inventory.list') }}",
                createUrl: '{{ route('create.inventory') }}',
                createPermissions: "{{ $createPermissions ?? '' }}",
                fetchId: "FetchData",
                title: "Inventory Management",
                createTitle: "Manually Create",
                displayLength: 30,
                is_import: "Upload Models",
                is_delete: "{{ $deletePermissions ?? '' }}",
                delete_url: "{{ route('delete.inventory') }}",
                importUrl: "{{ route('create.inventory') }}",
                is_export: "Export",
                manuall_create: false,
            };
            var filterData = {
                'status': {
                    'data': {
                        '1': 'WRITTEN',
                        '0': 'PENDING',
                        'all': 'ALL',
                    },
                    'filter_name': 'Filter By Status',
                },
            };

            console.log("filterData:", filterData);

            getDataTableS(options1, filterData, tableHeaders, getStats);

            function getStats(params) {
                console.log("Applied filters:", params);
            }

            $(".addNewRecordBtn").click(function() {
                window.location.href = '{{ route('create.inventory') }}';
            });


            // ========== Export bonding data via POST form submit (works for file download) ============= START ==================
            let exportUrl = "{{ route('inventory.exportInventory') }}";

            $(".exportBtn").click(function() {
                const selectedDaterange = document.getElementById('selectedDaterange')?.value || '';
                const statusFilter = document.getElementById('statusFilter')?.value || '';

                // create form
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = exportUrl;
                form.style.display = 'none';
                // optional: open in new tab (may be blocked by popup blockers)
                // form.target = '_blank';

                // CSRF token
                const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ||
                    '{{ csrf_token() }}';
                const _token = document.createElement('input');
                _token.type = 'hidden';
                _token.name = '_token';
                _token.value = token;
                form.appendChild(_token);

                // daterange
                const dr = document.createElement('input');
                dr.type = 'hidden';
                dr.name = 'daterange';
                dr.value = selectedDaterange;
                form.appendChild(dr);

                // status
                const st = document.createElement('input');
                st.type = 'hidden';
                st.name = 'status';
                st.value = statusFilter;
                form.appendChild(st);

                document.body.appendChild(form);
                form.submit();
                document.body.removeChild(form);
            });
            // ========== Export bonding data via POST form submit (works for file download) ============= END ==================


            $(".bulkImportBtn").click(function() {
                $("#bulkProductImportModal").modal('show');
            });
        });

        // Delete row
        function deleteRow(url) {
            console.log("Delete URL:", url);
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

                    if (response.success) {
                        toastr.success(response.message);
                        setTimeout(() => {
                            window.location.reload();
                        }, 1000);

                    } else {
                        toastr.error(response.message);
                    }
                },
                error: function(xhr, status, error) {
                    toastr.error("Error: " + error);
                }
            });
        }
    </script>



@endsection
