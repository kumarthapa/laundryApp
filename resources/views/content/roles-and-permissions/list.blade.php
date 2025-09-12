@extends("layouts/contentNavbarLayout")

@section("title", " Role - List")
@section("page-style")
    <link rel="stylesheet" href="{{ asset("assets/css/datatables.bootstrap5.css") }}">

@section("content")
    <!-- Basic Layout & Basic with Icons -->
    <div class="row g-4">
        {{-- @if (isset($role_info)) --}}
        @if (false)
            @foreach ($role_info as $role)
                <div class="col-xl-4 col-lg-6 col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between mb-2">
                                <h6 class="fw-normal">Total {{ isset($role->user_count) ? $role->user_count : 0 }} users</h6>
                                <ul class="list-unstyled d-flex align-items-center avatar-group mb-0">
                                    <li data-bs-toggle="tooltip" data-popup="tooltip-custom" data-bs-placement="top"
                                        class="avatar avatar-sm pull-up" aria-label="Julee Rossignol"
                                        data-bs-original-title="Julee Rossignol">
                                        <span class="avatar-initial rounded-circle bg-label-warning"><i
                                                class="bx bx-user"></i></span>
                                    </li>
                                    <li data-bs-toggle="tooltip" data-popup="tooltip-custom" data-bs-placement="top"
                                        class="avatar avatar-sm pull-up" aria-label="John Doe"
                                        data-bs-original-title="John Doe">
                                        <span class="avatar-initial rounded-circle bg-label-primary"><i
                                                class="bx bx-user"></i></span>
                                    </li>
                                </ul>
                            </div>
                            <div class="d-flex justify-content-between align-items-end">
                                <div class="role-heading">
                                    <h4 class="mb-3">{{ $role->role_name }}</h4>
                                    <a href="javascript:;"
                                        onclick="editRoleModal('{{ $role->role_id }}','{{ $role->role_name }}','{{ $role->user_type }}','{{ $role->status }}')"
                                        class="role-edit-modal"><i class="bx bxs-edit"></i>&nbsp;<small>Edit
                                            Role</small></a>
                                    <a href="{{ route("roles.create", $role->role_id) }}" class="role-edit-modal mx-2"><i
                                            class="bx bxs-edit"></i>&nbsp;<small>Edit
                                            Permissions</small></a>
                                </div>
                                @if ($role->status == 1)
                                    <div class="badge bg-label-success rounded ms-auto"><span>Active</span></div>
                                @else
                                    <div class="badge bg-label-danger rounded ms-auto"><span>Inactive</span></div>
                                @endif

                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        @endif
        <div class="col-12">
            <div class="card">
                <div class="card-datatable table-responsive pt-0">
                    <table class="datatables-basic table border-top" id="DataTables2024">
                    </table>
                </div>
            </div>
        </div>
    </div>
    @include("content.modals.editRole")
@endsection

@section("page-script")
    {{-- @include("content.roles-and-permissions.offcanvasRoleForm") --}}
    @include("content.modals.roleView")
    @include("content.partial.datatable")
    <script type="text/javascript">
        $(document).ready(function() {
            var tableHeaders = {!! $table_headers !!};
            var options1 = {
                url: '{{ route("roles.list") }}',
                createUrl: '{{ route("roles.create") }}',
                createPermissions: "{{ isset($createPermissions) ? $createPermissions : "" }}",
                fetchId: "FetchData",
                title: "Role list",
                createTitle: "Create New Role"
            };
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
            getDataTableS(options1, filterData, tableHeaders);
            $(".addNewRecordBtn").click(function() {
                window.location.href = "{{ route("roles.create") }}";
            })
        }); // end jquery document dot write


        function editRoleModal(role_id = '', rolename = '', user_type = '', status = '') {
            if (role_id) {
                $('#editRoleModal #edit_role_id').val(role_id);
                $('#editRoleModal #edit_role_name').val(rolename);
                $('#editRoleModal #user_type').val(user_type);
                if (status == '1') {
                    $('#editRoleModal #edit_role_status').prop('checked', true);
                } else {
                    $('#editRoleModal #edit_role_status').prop('checked', false);
                }
                $('#editRoleModal').modal('show');
            }
        }
        //Delete row
        function deleteRow(url) {
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
        function EditPermissions2() {
            let roleid = $('#edit_role_id_2').val();
            if (!roleid) return false;
            window.location.href = "{{ route("roles.create") }}" + '/' + roleid;
        }

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

                    console.log(response);
                    if (response.data) {
                        var user = response.data;
                        // Fill modal fields with user data
                        $('#edit_role_id_2').val(user.role_id)
                        $('#viewRowDetails #row-title').text(user.role_name);
                        $('#viewRowDetails #row-id').text(user.role_code);
                        $('#viewRowDetails #role_name').text(user.role_name);
                        $('#viewRowDetails #role_code').text(user.role_code);
                        $('#viewRowDetails #created_at').text(user.created_at);

                        if (user.status == 1) {
                            $('#viewRowDetails #status').html(
                                '<span class="badge bg-label-success">Active</span>');
                        } else {
                            $('#viewRowDetails #status').html(
                                '<span class="badge bg-label-secondary">Inactive</span>');
                        }

                        var htmlTable = '';
                        const grants_data = response.grants_permission;
                        if (grants_data) {
                            for (const module_id in grants_data) {
                                const permissions = grants_data[module_id];
                                let row = `<tr>
                            <td class="text-nowrap fw-medium">
                                <div class="form-check me-3 me-lg-5">
                                    <span class="badge bg-label-primary"><i class="bx bx-check-circle"></i></span>
                                    <label class="form-label text-uppercase" for="${module_id}">${module_id}</label>
                                </div>
                            </td>
                            <td>
                            <div class="d-flex">`;
                                for (const permission in permissions) {
                                    row += `<div class="me-3 me-lg-5">
                            <span class="badge bg-label-success"><i class="bx bx-check-circle"></i></span>
                            <label class="form-label" for="${permission}">${permission}</label>
                            </div>`;
                                    // const permissionName = permission.split('.')[0];
                                }
                                row += `</div>
                        </td>
                    </tr>`;
                                htmlTable += row;
                            }
                        }
                        // Append the constructed HTML to the table body
                        document.getElementById('permissionRoleViewTbody').innerHTML = htmlTable;

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
