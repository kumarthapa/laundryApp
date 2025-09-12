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
                <form class="needs-validation" onsubmit="return false" novalidate>
                    {{ csrf_field() }}
                    <div class="row px-3 py-3">


                        <div class="mb-4 text-center">
                            <h3 class="role-title">{{ $role_id ? 'Update Role' : 'Add New Role' }}</h3>
                            <p>Set role permissions</p>
                        </div>
                        <!-- Add role form -->
                        <div class="col-12 fv-plugins-icon-container mb-4">
                            <h4 for="RoleName">Enter Role Name</h4>
                            <input type="text" id="RoleName" name="RoleName" class="form-control"
                                placeholder="Enter a role name" tabindex="-1"
                                value="{{ isset($role_name) ? $role_name : '' }}">
                            <div
                                class="fv-plugins-message-container fv-plugins-message-container--enabled invalid-feedback">
                            </div>
                        </div>



                        <div class="col-12 fv-plugins-icon-container mb-2">
                            <label class="form-label" for="Status">Status</label>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" value="1" name="status"id="status"
                                    checked>
                            </div>
                        </div>
                        <div class="col-12 py-4">
                            {{-- <div class="d-flex justify-content-between">
                                <h4>Role Permissions</h4>
                                <a href="javascript:;" class="btn btn-label-primary" data-bs-target="#addNewPermission"
                                    data-bs-toggle="modal"><i class="bx bx-check-shield"></i>Add New Permission</a>
                            </div> --}}
                            <div>
                                <input type="text" id="searchInput" class="form-control my-3" placeholder="Search...">
                            </div>
                            <!-- Permission table -->
                            <div class="table-responsive">
                                <table class="table-flush-spacing table" id="permissionTable">
                                    <tbody>
                                        <tr>
                                            <td class="fw-medium text-nowrap">Administrator Access <i
                                                    class="bx bx-info-circle bx-xs" data-bs-toggle="tooltip"
                                                    data-bs-placement="top" aria-label="Allows a full access to the system"
                                                    data-bs-original-title="Allows a full access to the system"></i></td>
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
                                                            <h1 style="display: none;">MODULE ID: {{ $module_id }}</h1>
                                                            <input class="form-check-input all_permissions" type="checkbox"
                                                                value="all">
                                                            <label class="form-check-label" for="all_permission">
                                                                @lang('modules.' . $module_id)
                                                            </label>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <div class="row mx-0">
                                                            @if ($permission)
                                                                @foreach ($permission as $permission_id => $permission_name)
                                                                    <div class="col-6 col-md-3 form-check mb-1">
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

                                                                    {{-- @endif --}}
                                                                @endforeach
                                                            @endif
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        @endif
                                    </tbody>
                                </table>
                            </div>
                            <!-- Permission table -->
                        </div>
                        <!--/ Add role form -->
                        <div class="row">
                            <div class="col-12 text-end">
                                <button type="button" onclick="history.back()" class="btn btn-secondary me-2"
                                    aria-label="Back">
                                    Back</button>
                                <button type="submit" class="btn btn-primary">Submit</button>
                            </div>
                        </div>
                    </div>
                </form>

            </div> {{-- // card body end --}}
        </div> {{-- // card end --}}


    </div> {{-- // row end --}}
@endsection
@section('page-script')
    @include('content.modals.addPermission')
    <script>
        $(document).ready(function() {
            // Fetch all the forms we want to apply custom Bootstrap validation styles to
            var bsValidationForms = document.querySelectorAll(".needs-validation");
            Array.prototype.slice.call(bsValidationForms).forEach(function(form) {
                form.addEventListener(
                    "submit",
                    function(event) {
                        event.preventDefault(); // Prevent default form submission behavior

                        if (!form.checkValidity()) {
                            event.stopPropagation();
                        } else {
                            let role_id = "{{ isset($role_id) ? $role_id : '' }}";
                            // AJAX submission if validation passes
                            $.ajax({
                                type: 'POST',
                                url: "{{ route('roles.save') }}" + '/' +
                                    role_id, // Replace 'submit.form' with your actual route name
                                data: $(form).serialize(),
                                success: function(response) {
                                    // Show success notification

                                    if (response.success) {
                                        toastr.success(response.message);
                                        window.location.href = "{{ route('roles') }}";
                                    } else {
                                        toastr.error(response.message);
                                    }
                                },
                                error: function(xhr, status, error) {
                                    // Handle server-side validation errors
                                    var errors = xhr.responseJSON.errors;
                                    if (errors) {
                                        toastr.error(errors.join('<br>'));
                                    } else {
                                        toastr.error(
                                            'An error occurred while submitting the form.'
                                        );
                                    }
                                }
                            });
                        }

                        form.classList.add("was-validated");
                    },
                    false
                );
            });

            $('#searchInput').on('keyup', function() {
                var value = $(this).val().toLowerCase();
                $('#permissionTable tbody tr').filter(function() {
                    $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
                });
            });
            // --------------------------------------------------- All  Moudule Permission ------------------------------------------------------ //;
            // Select All checkbox change event
            $('#selectAll').on('change', function() {
                // Get the checked status of the Select All checkbox
                var isChecked = $(this).prop('checked');
                // Set all checkboxes in the table to the same checked status as the Select All checkbox
                $('.table-flush-spacing tbody input[type="checkbox"]').prop('checked', isChecked);
            });

            // Individual checkbox change event
            $('.table-flush-spacing tbody input[type="checkbox"]').on('change', function() {
                // Check if all checkboxes are checked and update the Select All checkbox accordingly
                var allChecked = $('.table-flush-spacing tbody input[type="checkbox"]').length === $(
                    '.table-flush-spacing tbody input[type="checkbox"]:checked').length;
                $('#selectAll').prop('checked', allChecked);
            });

            // --------------------------------------------------- All Individual Moudule Permission ------------------------------------------------------ //;
            // var test = $('.all_permissions').closest('tr').find('td input[type="checkbox"]').length;
            // console.log(test)

            // Select All checkbox change event
            $('.all_permissions').on('change', function() {
                // Get the checked status of the Select All checkbox
                var isChecked = $(this).prop('checked');
                // Set all checkboxes in the same row to the same checked status as the Select All checkbox
                $(this).closest('tr').find('td input[type="checkbox"]').prop('checked', isChecked);
            });

            // Individual checkbox change event
            $('.table-flush-spacing tbody input[type="checkbox"]').on('change', function() {
                var $row = $(this).closest('tr');
                var allChecked = $row.find('td input[type="checkbox"]').not('.all_permissions').length ===
                    $row.find('td input[type="checkbox"]:checked').not('.all_permissions').length;
                $row.find('.all_permissions').prop('checked', allChecked);
            });



        }); // end jquery document dot write
    </script>
@endsection
