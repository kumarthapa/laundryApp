@extends('layouts/contentNavbarLayout')
@section('title', ' Role and Permission - Form')
@section('page-style')
@endsection

@section('content')
    <div class="row mx-0">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title">Set user role and permission</h5>
            </div>
            <div class="card-body">
                <form class="needs-validation" id="roleForm" novalidate>
                    {{ csrf_field() }}
                    <div class="row px-3 py-2">

                        <div class="mb-4 text-center">
                            <h3 class="role-title">{{ $role_id ? 'Update Role' : 'Add New Role' }}</h3>
                            <p>Set role permissions</p>
                        </div>

                        <!-- Role Name -->
                        <div class="col-md-6 col-12 fv-plugins-icon-container mb-4">
                            <h4 for="RoleName">Enter Role Name</h4>
                            <input type="text" id="RoleName" name="RoleName" class="form-control"
                                placeholder="Enter a role name" value="{{ isset($role_name) ? $role_name : '' }}" required>
                            <div class="invalid-feedback">
                                Please enter a role name.
                            </div>
                        </div>

                        <!-- Role Type -->
                        @if ($is_super_admin)
                            <div class="col-md-6 col-12 fv-plugins-icon-container mb-4">
                                <h4 for="role_type">Role Type</h4>
                                <select id="role_type" name="role_type" class="form-select" required>
                                    <option value="">Select role type</option>
                                    @foreach ($role_types ?? [] as $value => $name)
                                        <option value="{{ $value }}"
                                            {{ isset($role_info->role_type) && $role_info->role_type == $value ? 'selected' : '' }}>
                                            {{ $name ?? '' }}
                                        </option>
                                    @endforeach
                                </select>
                                <div class="invalid-feedback">
                                    Please select a role type.
                                </div>
                            </div>
                        @endif

                        <!-- Status -->
                        <div class="col-12 fv-plugins-icon-container mb-2">
                            <label class="form-label" for="Status">Status</label>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" value="1" name="status" id="status"
                                    checked>
                            </div>
                        </div>

                        <!-- Permissions -->
                        <div class="col-12 py-2">
                            <div>
                                <input type="text" id="searchInput" class="form-control my-3" placeholder="Search...">
                            </div>

                            <div class="table-responsive">
                                <table class="table-flush-spacing table" id="permissionTable">
                                    <tbody>
                                        <tr>
                                            <td class="fw-medium text-nowrap">
                                                Administrator Access
                                                <i class="bx bx-info-circle bx-xs" data-bs-toggle="tooltip"
                                                    data-bs-placement="top" aria-label="Allows full access to the system"
                                                    data-bs-original-title="Allows full access to the system"></i>
                                            </td>
                                            <td>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" value="all"
                                                        id="selectAll">
                                                    <label class="form-check-label" for="selectAll">
                                                        Select All
                                                    </label>
                                                </div>
                                            </td>
                                        </tr>

                                        @if ($module_permission)
                                            @foreach ($module_permission as $module_id => $permission)
                                                <tr>
                                                    <td class="fw-medium text-nowrap">
                                                        <div class="form-check me-lg-5 me-3">
                                                            <input class="form-check-input all_permissions" type="checkbox"
                                                                value="all">
                                                            <label class="form-check-label" for="all_permission">
                                                                @lang('modules.' . $module_id)
                                                            </label>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <div class="row mx-0">
                                                            @foreach ($permission as $permission_id => $permission_name)
                                                                <div class="col-12 col-md-3 form-check mb-1">
                                                                    <input class="form-check-input" type="checkbox"
                                                                        @if ($module_id == 'roles' && $permission_id == 'create.roles') onclick="return false" @endif
                                                                        name="{{ $module_id }}[]"
                                                                        value="{{ $permission_id }}"
                                                                        @if (isset($grants_permission[$module_id][$permission_id]) &&
                                                                                $grants_permission[$module_id][$permission_id] == $permission_id) checked @endif>
                                                                    <label class="form-check-label"
                                                                        for="{{ $permission_id }}">
                                                                        {{ $permission_name }}
                                                                    </label>
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        @endif
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="row">
                            <div class="col-12 text-end">
                                <button type="button" onclick="history.back()" class="btn btn-secondary me-2"
                                    aria-label="Back">
                                    Back
                                </button>
                                <button type="submit" class="btn btn-primary">Submit</button>
                            </div>
                        </div>

                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('page-script')
    @include('content.modals.addPermission')
    <script>
        $(document).ready(function() {

            // Form validation & AJAX submission
            const form = document.getElementById("roleForm");

            form.addEventListener("submit", function(event) {
                event.preventDefault();
                event.stopPropagation();

                if (form.checkValidity()) {
                    let role_id = "{{ isset($role_id) ? $role_id : '' }}";

                    $.ajax({
                        type: "POST",
                        url: "{{ route('roles.save') }}/" + role_id,
                        data: $(form).serialize(),
                        success: function(response) {
                            if (response.success) {
                                toastr.success(response.message);
                                window.location.href = "{{ route('roles') }}";
                            } else {
                                toastr.error(response.message);
                            }
                        },
                        error: function(xhr) {
                            toastr.error("An error occurred while submitting the form.");
                        }
                    });
                }

                form.classList.add("was-validated");
            });

            // Search permissions
            $('#searchInput').on('keyup', function() {
                var value = $(this).val().toLowerCase();
                $('#permissionTable tbody tr').filter(function() {
                    $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
                });
            });

            // Select all permissions
            $('#selectAll').on('change', function() {
                var isChecked = $(this).prop('checked');
                $('.table-flush-spacing tbody input[type="checkbox"]').prop('checked', isChecked);
            });

            // Update selectAll checkbox
            $('.table-flush-spacing tbody input[type="checkbox"]').on('change', function() {
                var allChecked = $('.table-flush-spacing tbody input[type="checkbox"]').length ===
                    $('.table-flush-spacing tbody input[type="checkbox"]:checked').length;
                $('#selectAll').prop('checked', allChecked);
            });

            // Module-level select all
            $('.all_permissions').on('change', function() {
                var isChecked = $(this).prop('checked');
                $(this).closest('tr').find('td input[type="checkbox"]').prop('checked', isChecked);
            });

            // Update module-level checkbox
            $('.table-flush-spacing tbody input[type="checkbox"]').on('change', function() {
                var $row = $(this).closest('tr');
                var allChecked = $row.find('td input[type="checkbox"]').not('.all_permissions').length ===
                    $row.find('td input[type="checkbox"]:checked').not('.all_permissions').length;
                $row.find('.all_permissions').prop('checked', allChecked);
            });

            // Initialize select2
            $("#role_type").select2({
                allowClear: true
            });

        });
    </script>
@endsection
