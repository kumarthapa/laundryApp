<div class="modal fade" id="editRoleModal" tabindex="-1" aria-modal="true" role="dialog">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="modalCenterTitle"> Update Role Name</h4>
                <div>
                    <button type="button" class="btn btn-primary btn-sm mx-2" onclick="EditPermissions()">
                        Edit
                        Permissions
                    </button>
                    <button type="button" class="btn btn-label-danger btn-sm" data-bs-dismiss="modal"
                        aria-label="Close">
                        <i class='bx bx-x'></i>
                    </button>
                </div>
            </div>
            <div class="modal-body">
                <!-- edit role form -->
                <form id="role_edit_form" class="row g-3 fv-plugins-bootstrap5 fv-plugins-framework role_edit_form"
                    novalidate="novalidate">
                    {{ csrf_field() }}
                    <div class="col-12 fv-plugins-icon-container mb-2">
                        <div id="nameError" class="text-danger"></div> <!-- Placeholder for validation error -->
                        <label class="form-label" for="edit_role_name">Role Name</label>
                        <input type="text" id="edit_role_name" name="RoleName" class="form-control"
                            placeholder="Enter a role name" tabindex="-1" required>
                        <div class="form-check me-lg-5 me-3 py-3">
                            <input class="form-check-input" type="checkbox" name="status" value="1"
                                id="edit_role_status">
                            <label class="form-check-label" for="status">Status</label>
                        </div>
                        <div
                            class="fv-plugins-message-container fv-plugins-message-container--enabled invalid-feedback">
                        </div>
                    </div>
                    <div class="col-12 text-end">
                        <button type="reset" class="btn btn-label-secondary" data-bs-dismiss="modal"
                            aria-label="Close">Cancel</button>
                        <button type="submit" class="btn btn-primary me-1">Submit</button>
                    </div>
                    <input type="hidden" id="edit_role_id" name="role_id">
                    <input type="hidden" id="isRoleUpdate" name="isRoleUpdate" value="1">
                </form>
                <!--/ Add role form -->
            </div>
        </div>
    </div>
</div>
<script>
    function EditPermissions() {
        let roleid = $('#edit_role_id').val();
        if (!roleid) return false;
        window.location.href = "{{ route('roles.create') }}" + '/' + roleid;
    }
    // Fetch all the forms we want to apply custom Bootstrap validation styles to
    var bsValidationForms = document.querySelectorAll(".role_edit_form");
    Array.prototype.slice.call(bsValidationForms).forEach(function(form) {
        form.addEventListener(
            "submit",
            function(event) {
                event.preventDefault(); // Prevent default form submission behavior
                if (!form.checkValidity()) {
                    event.stopPropagation();
                } else {
                    let role_id = $('#edit_role_id').val();
                    console.log(role_id);
                    if (!role_id) return false;
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
</script>
