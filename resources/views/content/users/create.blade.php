@extends('layouts/contentNavbarLayout')

@section('title', 'Users - Form')

@section('page-style')
    <style>
        .invalid-feedback {
            display: none;
        }

        .is-invalid .invalid-feedback {
            display: block;
        }

        /* Password toggle icon */
        .password-toggle {
            cursor: pointer;
        }
    </style>
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header border-bottom">
                    <h5 class="card-title">{{ isset($user_id) ? 'Update User Information' : 'Add New User' }}</h5>
                </div>
                <div class="card-body">
                    <form class="needs-validation" novalidate>
                        {{ csrf_field() }}
                        <div class="row px-3 py-3" id="users_form">

                            <!-- Full Name -->
                            <div class="col-md-6 col-12 mb-3">
                                <label for="fullName" class="form-label text-danger">Full Name</label>
                                <input type="text" id="fullName" name="fullName" class="form-control"
                                    placeholder="Full name" value="{{ $info->fullname ?? '' }}" required />
                                <div class="invalid-feedback">Please enter the full name</div>
                            </div>

                            <!-- User Locations -->

                            @if (!$is_super_admin)
                                <div class="col-md-6 col-12 mb-3">
                                    <label for="user_location_id" class="form-label text-danger">User Location</label>
                                    <select id="user_location_id" name="user_location_id" class="form-select">
                                        @foreach ($locations_info ?? [] as $location)
                                            <option value="{{ $location->location_id }}"
                                                {{ isset($info->location_id) && $info->location_id == $location->location_id ? 'selected' : '' }}>
                                                {{ $location->location_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <div class="invalid-feedback">Please select a location</div>
                                </div>
                            @endif

                            <!-- User Role -->
                            @if (isset($is_super_admin) && $is_super_admin)
                                <div class="col-md-6 col-12 mb-3">
                                    <label for="user_role_id" class="form-label text-danger">User Role</label>
                                    <select id="user_role_id" name="user_role_id" class="form-select" required>
                                        {{-- @if (isset($info->id) && $info->role_id) disabled @endif required> --}}
                                        <option value="">Select Role</option>
                                        @foreach ($roles_info ?? [] as $roles)
                                            <option value="{{ $roles->role_id }}"
                                                {{ isset($info->role_id) && $info->role_id == $roles->role_id ? 'selected' : '' }}>
                                                {{ $roles->role_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <div class="invalid-feedback">Please select a role</div>
                                </div>
                            @else
                                <div class="col-md-6 col-12 mb-3">
                                    <label for="user_role_id" class="form-label text-danger">User Role</label>
                                    <select id="user_role_id" name="user_role_id" class="form-select" required>
                                        <option value="{{ $admin_roles_info->role_id ?? 2 }}">
                                            {{ $admin_roles_info->role_name ?? 'admin' }}
                                        </option>

                                    </select>
                                    <div class="invalid-feedback">Please select a role</div>
                                </div>
                            @endif
                            <!-- Email -->
                            <div class="col-md-6 col-12 mb-3">
                                <label for="userEmail" class="form-label">Email</label>
                                <input type="email" id="userEmail" name="userEmail" class="form-control"
                                    placeholder="treewalker@example.com" value="{{ $info->email ?? '' }}" />
                                <div class="invalid-feedback">Please enter a valid email</div>
                            </div>

                            <!-- Contact -->
                            <div class="col-md-6 col-12 mb-3">
                                <label for="userContact" class="form-label">Contact</label>
                                <input type="text" id="userContact" name="userContact" class="form-control phone-mask"
                                    placeholder="+1 (609) 988-44-11" value="{{ $info->contact ?? '' }}" />
                                <div class="invalid-feedback">Please enter a valid contact</div>
                            </div>

                            <!-- Username -->
                            <div class="col-md-6 col-12 mb-3">
                                <label for="userName" class="form-label">Username</label>
                                <input type="text" id="userName" name="userName" class="form-control"
                                    placeholder="Username" value="{{ $info->username ?? '' }}" required />
                                <div class="invalid-feedback">Please enter a username and avoid spaces</div>
                            </div>
                            <!-- Status Switch -->
                            <div class="col-md-6 col-12 mb-3">
                                <label for="status" class="form-label d-block">Status Is Active</label>
                                <div class="form-check form-switch ms-3">
                                    <input type="checkbox" id="status" name="status" class="form-check-input fs-4"
                                        value="1"
                                        {{ isset($info->status) && strtolower($info->status) === 'Active' ? 'checked' : 'checked' }}>
                                </div>
                            </div>
                            <!-- set super admin Switch -->
                            <div class="col-md-6 col-12 mb-3">
                                <label for="status" class="form-label d-block">Is Super admin</label>
                                <div class="form-check form-switch ms-3">
                                    <input type="checkbox" id="is_super_admin" name="is_super_admin"
                                        class="form-check-input fs-4" value="1"
                                        {{ isset($info->is_super_admin) && strtolower($info->is_super_admin) === 1 ? 'checked' : '' }}>
                                </div>
                            </div>
                            <!-- Password Change Section -->
                            <div class="col-md-6 col-12 mb-3">
                                <label class="form-label d-flex align-items-center" for="userPassWord">
                                    Password
                                    @if (isset($user_id))
                                        <i class="bx bx-edit-alt password-toggle ms-2" id="togglePassword"
                                            title="Change Password" style="font-size: 1.25rem;"></i>
                                    @endif
                                </label>

                                {{-- New user: visible and required password input --}}
                                @if (!isset($user_id))
                                    <input type="password" id="userPassWord" name="userPassWord" class="form-control"
                                        placeholder="Enter password" autocomplete="new-password" required />
                                @else
                                    {{-- Update user: hidden and disabled password input initially --}}
                                    <input type="password" id="userPassWord" name="userPassWord"
                                        class="form-control d-none" placeholder="Enter new password"
                                        autocomplete="new-password" disabled />
                                @endif

                                <div class="invalid-feedback">Please enter your password.</div>
                            </div>



                        </div>

                        <div class="row">
                            <div class="col-12 text-end">
                                <button type="button" onclick="history.back()" class="btn btn-secondary me-2"
                                    aria-label="Back">Back</button>
                                <button type="submit" class="btn btn-primary">Submit</button>
                            </div>
                        </div>
                    </form>
                </div>{{-- card-body end --}}
            </div>{{-- card end --}}
        </div>
    </div>{{-- row end --}}
@endsection

@section('page-script')
    <script>
        $(document).ready(function() {
            @if (isset($user_id))
                // Password toggle click handler for update user form
                $("#togglePassword").click(function() {
                    let input = $("#userPassWord");
                    if (input.hasClass("d-none")) {
                        input.removeClass("d-none").prop("disabled", false).val('').focus();
                    } else {
                        input.addClass("d-none").prop("disabled", true).val('');
                    }
                });
            @endif

            $('#user_location_id').select2({
                placeholder: "Select Location",
                allowClear: true,
                width: '100%'
            });
            $('#user_role_id').select2({
                placeholder: "Select Role",
                allowClear: true,
                width: '100%'
            });

            // Check username has spaces on input
            $('#userName').on('change', function() {
                let username = $(this).val();
                if (/\s/.test(username)) {
                    $(this).addClass('is-invalid');
                    $(this).next('.invalid-feedback').text("Username cannot contain spaces");
                    // $(this).val('');
                } else {
                    $(this).removeClass('is-invalid');
                    $(this).next('.invalid-feedback').text("Please enter a username and avoid spaces");
                }
            });



            // Bootstrap form validation and AJAX submission including username space check
            var forms = document.querySelectorAll(".needs-validation");
            Array.prototype.slice.call(forms).forEach(function(form) {
                form.addEventListener("submit", function(event) {
                    event.preventDefault();

                    const userNameInput = form.querySelector('input[name="userName"]');
                    if (/\s/.test(userNameInput.value)) {
                        event.stopPropagation();
                        userNameInput.classList.add('is-invalid');
                        userNameInput.nextElementSibling.textContent =
                            "Username cannot contain spaces";
                        return;
                    } else {
                        userNameInput.classList.remove('is-invalid');
                        userNameInput.nextElementSibling.textContent =
                            "Please enter a username and avoid spaces";
                    }

                    if (!form.checkValidity()) {
                        event.stopPropagation();
                        return;
                    }

                    var submitButton = $(form).find('button[type="submit"]');
                    submitButton.prop('disabled', true).text('Submitting...');
                    let user_id = "{{ $user_id ?? '' }}";

                    $.ajax({
                        type: "POST",
                        url: "{{ route('users.save') }}" + '/' + user_id,
                        data: $(form).serialize(),
                        success: function(response) {
                            if (response.success) {
                                toastr.success(response.message ??
                                    'Form submitted successfully');
                                window.location.href = "{{ route('users') }}";
                            } else {
                                toastr.error(response.message ?? 'Submit Failed!');
                                submitButton.prop('disabled', false).text('Submit');
                            }
                        },
                        error: function(xhr) {
                            var errors = xhr.responseJSON?.errors;
                            if (errors) {
                                toastr.error(Object.values(errors).flat().join("<br>"));
                            } else {
                                toastr.error(
                                    "An error occurred while submitting the form.");
                            }
                            submitButton.prop('disabled', false).text('Submit');
                        }
                    });

                    form.classList.add("was-validated");
                }, false);
            });
        });
    </script>
    @include('content.users.script')
@endsection
