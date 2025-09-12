<div class="modal fade" id="addNewPermission" tabindex="-1" aria-modal="true" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content p-3 p-md-5">
            <div class="modal-body">
                <div class="modal-header">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                    </button>
                </div>
                <div class="text-center mb-4">
                    <h3><i class="bx bx-check-shield fs-2"></i>Add Permission</h3>
                    <p>Create a new module permission</p>
                </div>
                <!-- edit role form -->
                <form id="add_permission_form"
                    class="row g-3 fv-plugins-bootstrap5 fv-plugins-framework add_permission_form"
                    novalidate="novalidate">
                    {{ csrf_field() }}
                    <div class="col-12 mb-2 fv-plugins-icon-container">
                        <div class="mb-3">
                            <label class="form-label" for="permission_name">Permission Name</label>
                            <input type="text" id="permission_name" name="permission_name" class="form-control"
                                placeholder="Enter a permission name" tabindex="-1" required>
                            <div class="invalid-feedback">Enter Permission Name!</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="module_id">Select Modules</label>
                            <select class="form-select" id="module_id" name="module_id" required>
                                <option value="">Select an option</option>
                                @if (isset($module_permission) && $module_permission)
                                    @foreach ($module_permission as $module_id => $permission)
                                        <option value="{{ $module_id }}">@lang('roles.' . $module_id)</option>
                                    @endforeach
                                @endif
                            </select>
                            <div class="invalid-feedback">Select Module Name!</div>
                        </div>
                    </div>
                    <div class="col-12 text-center">
                        <button type="reset" class="btn btn-label-secondary" data-bs-dismiss="modal"
                            aria-label="Close">Cancel</button>
                        <button type="submit" class="btn btn-primary me-sm-3 me-1">Submit</button>
                    </div>
                </form>
                <!--/ Add role form -->
            </div>
        </div>
    </div>
</div>
<script>
    // $("#permission_name").on('change', function() {
    //     // Your code here
    //     console.log("Key up event detected.");
    //     // For example, you could get the value of the input field like this:
    //     let permissionName = $(this).val();
    //     console.log("Current value:", permissionName);
    // });
    // Fetch all the forms we want to apply custom Bootstrap validation styles to
    var bsValidationForms = document.querySelectorAll(".add_permission_form");
    Array.prototype.slice.call(bsValidationForms).forEach(function(form) {
        form.addEventListener(
            "submit",
            function(event) {
                event.preventDefault(); // Prevent default form submission behavior
                if (!form.checkValidity()) {
                    event.stopPropagation();
                } else {
                    //return false;
                    // AJAX submission if validation passes
                    $.ajax({
                        type: 'POST',
                        url: "{{ route('roles.saveModulePermissions') }}",
                        data: $(form).serialize(),
                        success: function(response) {
                            // Show success notification
                            if (response.success) {
                                toastr.success(response.message);
                                setTimeout(function() {
                                    location.reload();
                                }, 1000)
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
</script>
