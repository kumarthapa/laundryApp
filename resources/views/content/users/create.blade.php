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

        /* Password toggle icon (generic) */
        .password-toggle {
            cursor: pointer;
        }

        /* Eye icon placed inside input */
        .password-input-wrapper {
            position: relative;
        }

        .password-input-wrapper .view-toggle {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            font-size: 1.05rem;
            color: #6c757d;
            /* muted */
            z-index: 5;
        }

        /* ensure the eye is not visible when input hidden */
        .password-input-wrapper .view-toggle.d-none {
            display: none !important;
        }

        /* Select2 invalid state — style the container when invalid */
        .select2-container.is-invalid .select2-selection {
            border-color: #dc3545;
            box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, .075);
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
                                    <select id="user_location_id" name="user_location_id" class="form-select" required>
                                        <option value="">Select Location</option>
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
                            {{-- @if (isset($is_super_admin) && $is_super_admin) --}}
                            <div class="col-md-6 col-12 mb-3">
                                <label for="user_role_id" class="form-label text-danger">User Role</label>
                                <select id="user_role_id" name="user_role_id" class="form-select" required>
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
                            {{-- @else
                                <div class="col-md-6 col-12 mb-3">
                                    <label for="user_role_id" class="form-label text-danger">User Role</label>
                                    <select id="user_role_id" name="user_role_id" class="form-select" required>
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
                            @endif --}}

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
                                        {{ isset($info->status) && strtolower($info->status) === 'active' ? 'checked' : 'checked' }}>
                                </div>
                            </div>

                            <!-- Password Change Section  ------ START------------>
                            <div class="col-md-6 col-12 mb-3">
                                <label class="form-label d-flex align-items-center" for="userPassWord">
                                    Password
                                    @if (isset($user_id))
                                        <!-- edit-password icon: reveals the password input for updates -->
                                        <i class="bx bx-edit-alt password-toggle ms-2" id="togglePassword"
                                            title="Change Password" style="font-size: 1.25rem;"></i>
                                    @endif
                                </label>

                                <div class="password-input-wrapper">
                                    {{-- New user: visible and required password input --}}
                                    @if (!isset($user_id))
                                        <input type="password" id="userPassWord" name="userPassWord" class="form-control"
                                            placeholder="Enter password" autocomplete="new-password" required />
                                        <!-- Eye icon: toggles show/hide -->
                                        <i id="passwordViewToggle" class="bx bx-hide view-toggle" title="Show password"
                                            aria-hidden="true"></i>
                                    @else
                                        {{-- Update user: hidden and disabled password input initially --}}
                                        <input type="password" id="userPassWord" name="userPassWord"
                                            class="form-control d-none" placeholder="Enter new password"
                                            autocomplete="new-password" disabled />
                                        <i id="passwordViewToggle" class="bx bx-hide view-toggle d-none"
                                            title="Show password" aria-hidden="true"></i>
                                    @endif
                                </div>

                                <div class="invalid-feedback">Please enter your password.</div>
                            </div>
                            <!-- Password Change Section  ------ END------------>

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
                // Password "Change Password" behavior for update form
                $("#togglePassword").click(function() {
                    let input = $("#userPassWord");
                    let viewToggle = $("#passwordViewToggle");

                    if (input.hasClass("d-none")) {
                        // show input and enable it
                        input.removeClass("d-none").prop("disabled", false).val('').focus();
                        // show eye icon
                        viewToggle.removeClass("d-none");
                    } else {
                        // hide input and disable it, reset state
                        input.addClass("d-none").prop("disabled", true).val('');
                        // hide eye icon and reset to hidden state
                        viewToggle.addClass("d-none").removeClass("bx-show").addClass("bx-hide")
                            .attr("title", "Show password");
                        input.attr('type', 'password');
                    }
                });
            @endif

            // Toggle password show/hide (eye icon)
            $("#passwordViewToggle").on('click', function() {
                let input = $("#userPassWord");
                // if input is disabled or not visible, do nothing
                if (input.prop('disabled') || input.hasClass('d-none')) return;

                let $icon = $(this);
                if (input.attr('type') === 'password') {
                    input.attr('type', 'text');
                    // swap icon to "showing" state
                    $icon.removeClass('bx-hide').addClass('bx-show');
                    $icon.attr('title', 'Hide password');
                } else {
                    input.attr('type', 'password');
                    $icon.removeClass('bx-show').addClass('bx-hide');
                    $icon.attr('title', 'Show password');
                }
            });

            // Initialize Select2
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

            // Clear invalid state when select2 value changes
            $('#user_location_id').on('change.select2', function() {
                if ($(this).val() && $(this).val().length) {
                    $(this).removeClass('is-invalid');
                    $(this).next('.select2-container').removeClass('is-invalid');
                } else {
                    // if empty and required, keep invalid handled by submit
                    $(this).removeClass('is-invalid');
                    $(this).next('.select2-container').removeClass('is-invalid');
                }
            });
            $('#user_role_id').on('change.select2', function() {
                if ($(this).val() && $(this).val().length) {
                    $(this).removeClass('is-invalid');
                    $(this).next('.select2-container').removeClass('is-invalid');
                } else {
                    $(this).removeClass('is-invalid');
                    $(this).next('.select2-container').removeClass('is-invalid');
                }
            });

            // Check username has spaces on input (live)
            $('#userName').on('change input', function() {
                let username = $(this).val();
                if (/\s/.test(username)) {
                    $(this).addClass('is-invalid');
                    $(this).next('.invalid-feedback').text("Username cannot contain spaces");
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
                    event.stopPropagation();

                    // Add Bootstrap validation class immediately so invalid-feedback shows
                    form.classList.add("was-validated");

                    // Validate select2 fields manually (they need custom handling)
                    function validateSelect2(selector) {
                        var $sel = $(selector);
                        if ($sel.length && $sel.prop('required')) {
                            if (!$sel.val() || $sel.val().length === 0) {
                                $sel.addClass('is-invalid');
                                $sel.next('.select2-container').addClass('is-invalid');
                            } else {
                                $sel.removeClass('is-invalid');
                                $sel.next('.select2-container').removeClass('is-invalid');
                            }
                        }
                    }
                    validateSelect2('#user_location_id');
                    validateSelect2('#user_role_id');

                    // Username space check
                    const userNameInput = form.querySelector('input[name="userName"]');
                    if (/\s/.test(userNameInput.value)) {
                        userNameInput.classList.add('is-invalid');
                        userNameInput.nextElementSibling.textContent =
                            "Username cannot contain spaces";
                        return; // stop: invalid username
                    } else {
                        userNameInput.classList.remove('is-invalid');
                        userNameInput.nextElementSibling.textContent =
                            "Please enter a username and avoid spaces";
                    }

                    // If native validity fails, bail out (invalid-feedback will be visible because of was-validated)
                    if (!form.checkValidity()) {
                        return;
                    }

                    // Passed validation — proceed with AJAX submit
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
                }, false);
            });
        });
    </script>

    @include('content.users.script')
@endsection
