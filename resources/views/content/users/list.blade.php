@extends('layouts/contentNavbarLayout')

@section('title', ' Users - List')
@section('page-style')
    <link rel="stylesheet" href="{{ asset('assets/css/datatables.bootstrap5.css') }}">
@endsection
@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card mb-4">
                <div class="card-widget-separator-wrapper">
                    <div class="card-body card-widget-separator">
                        <div class="row gy-4 gy-sm-1">
                            <div class="col-sm-6 col-lg-3">
                                <div
                                    class="d-flex justify-content-between align-items-start card-widget-1 border-end pb-sm-0 pb-3">
                                    <div>
                                        <h3 class="mb-1">
                                            {{ isset($usersOverview['total_users']) ? $usersOverview['total_users'] : '0' }}
                                        </h3>
                                        <p class="mb-0">Total Users</p>
                                    </div>
                                    <span class="avatar-initial bg-label-primary me-sm-4 rounded p-2">
                                        <i class="bx bx-user bx-sm"></i>
                                    </span>
                                </div>
                                <hr class="d-none d-sm-block d-lg-none me-4">
                            </div>
                            <div class="col-sm-6 col-lg-3">
                                <div
                                    class="d-flex justify-content-between align-items-start card-widget-2 border-end pb-sm-0 pb-3">
                                    <div>
                                        <h3 class="mb-1">
                                            {{ isset($usersOverview['total_active']) ? $usersOverview['total_active'] : '0' }}
                                        </h3>
                                        <p class="mb-0">Active Users</p>
                                    </div>
                                    <span class="avatar-initial bg-label-success me-sm-4 rounded p-2">
                                        <i class="bx bx-user-check bx-sm"></i>
                                    </span>
                                </div>
                                <hr class="d-none d-sm-block d-lg-none">
                            </div>
                            <div class="col-sm-6 col-lg-3">
                                <div
                                    class="d-flex justify-content-between align-items-start border-end pb-sm-0 card-widget-3 pb-3">
                                    <div>
                                        <h3 class="mb-1">
                                            {{ isset($usersOverview['total_pending']) ? $usersOverview['total_pending'] : '0' }}
                                        </h3>
                                        <p class="mb-0">Pending Users</p>
                                    </div>
                                    <span class="badge bg-label-danger me-sm-4 rounded p-2">
                                        <i class="bx bx-info-circle bx-sm"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row g-4">
        <div class="col-md-12">
            <div class="card">
                {{-- <select id="filterDropdown" class="form-select">
                    <option value="">Select Filter</option>
                    <option value="option1">Option 1</option>
                    <option value="option2">Option 2</option>
                    <!-- Add more options as needed -->
                </select> --}}
                <div class="card-datatable table-responsive pt-0">
                    <table class="datatables-basic border-top table" id="DataTables2025">
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('page-script')
    {{-- @include('content.partial.modal.custom_filters', ['user_types' => $userTypes]) --}}
    @include('content.modals.userView')
    @include('content.partial.datatable')
    <script type="text/javascript">
        $(document).ready(function() {
            var tableHeaders = {!! $table_headers !!};
            var filterData = {
                'status': {
                    'data': {
                        'all': 'ALL',
                        'active': 'ACTIVE',
                        'inactive': 'INACTIVE',
                        'pending': 'PENDING'
                    },
                    'filter_name': 'Filter By Status',
                }
            };

            var options1 = {
                url: "{{ route('users.list') }}",
                createUrl: "{{ route('users.create') }}",
                createPermissions: "{{ isset($createPermissions) ? $createPermissions : '' }}",
                fetchId: "FetchData",
                title: "User list",
                createTitle: 'Add User',
                manuall_create: true,
                is_delete: "",
            };
            getDataTableS(options1, filterData, tableHeaders);
            $(".addNewRecordBtn").click(function() {
                window.location.href = "{{ route('users.create') }}";
            })

        }); // end jquery document dot write

        //Delete row
        function deleteRow(url) {
            // Display a confirmation dialog to the user
            if (!confirm("Are you sure you want to delete this user?")) {
                return false;
            }
            $.ajax({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                url: url, // URL for the AJAX call
                method: 'POST',
                success: function(response) {
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
        // View row details
        function viewRowDetails(url) {
            // Display a confirmation dialog to the user
            // Get user code data
            $.ajax(url, {
                data: {
                    term: '',
                    start: 1
                },
                dataType: 'json',
                success: function(response) {

                    console.log(response.data);
                    if (response.data) {
                        var user = response.data;
                        // Fill modal fields with user data
                        $('#viewRowDetails #row-title').text(user.fullname);
                        $('#viewRowDetails #row-email').text(user.email);
                        $('#viewRowDetails #name').text(user.fullname);
                        $('#viewRowDetails #user_code').text(user.user_code);
                        $('#viewRowDetails #contact').text(user.contact);
                        $('#viewRowDetails #email').text(user.email);
                        $('#viewRowDetails #role_name').text(user.role_id);
                        $('#viewRowDetails #username').text(user.username);
                        $('#viewRowDetails #created_at').text(user.created_at);
                        if (user.status == "Active") {
                            $('#viewRowDetails #status').html(
                                '<span class="badge bg-label-success">Active</span>');
                        } else {
                            $('#viewRowDetails #status').html(
                                '<span class="badge bg-label-secondary">Inactive</span>');
                        }
                        // Show the modal
                        $('#viewRowDetails').modal('show');
                    } else {
                        alert("Failed! to Display Data");
                        return false;
                    }
                }
            });
            // call your ajax here
        }
    </script>
@endsection
