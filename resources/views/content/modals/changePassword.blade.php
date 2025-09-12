{{-- <div class="modal fade" id="addNewPermission" tabindex="-1" aria-modal="true" role="dialog">
  <div class="modal-dialog">
      <div class="modal-content p-3 p-md-5">
          <div class="modal-body">

              <!--/ Add role form -->
          </div>
      </div>
  </div>
</div> --}}
<div class="modal fade" id="change_password" tabindex="-1" aria-modal="true" role="dialog">
  <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content ">
          <div class="modal-header">
              <h4 class="modal-title" id="modalCenterTitle"> Change Password</h4>
              <button type="button" class="btn btn-label-danger p-2" data-bs-dismiss="modal" aria-label="Close">
                  <i class='bx bx-x'></i>
              </button>
          </div>
          <div class="modal-body">

          {{-- <div class="text-center mb-4">
              <h3><i class="bx bx-check-shield fs-2"></i>Reset Password</h3>
              <p>Create a new password</p>
          </div> --}}
          <!-- edit role form -->
          <form id="add_permission_form"
              class="row g-3 fv-plugins-bootstrap5 fv-plugins-framework add_permission_form"
              novalidate="novalidate">
              {{ csrf_field() }}
              <div class="col-12 mb-2 fv-plugins-icon-container">
                <input type="password" id="email" name="email" value="{{$email}}" class="form-control"
                          tabindex="-1" style="display: none">
                  <div class="mb-3">
                    <label class="form-label" for="old_password"> Old Password</label>
                      <input type="password" id="old_password" name="old_password" class="form-control"
                        tabindex="-1" required>
                      <div class="invalid-feedback">Enter old password</div>
                  </div>
                  <div class="mb-3">
                      <label class="form-label" for="new_password">New Password</label>
                      <input type="password" id="new_password" name="new_password" class="form-control"
                          tabindex="-1" required>
                      <div class="invalid-feedback">Enter new password</div>
                  </div>
                  <div class="mb-3">
                      <label class="form-label" for="confirm_password">Confirm Password</label>
                      <input type="password" id="confirm_password" name="confirm_password" class="form-control"
                          tabindex="-1" required>
                      <div class="invalid-feedback">Confirm password</div>
                  </div>
              </div>
              <div class="col-12 text-center">
                  <button type="reset" class="btn btn-label-secondary" data-bs-dismiss="modal"
                      aria-label="Close">Cancel</button>
                  <button type="submit" class="btn btn-primary me-sm-3 me-1">Submit</button>
              </div>
          </form>
          </div>
          <div class="modal-footer">
              {{-- <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Close</button>
              <button type="button" class="btn btn-primary">Save changes</button> --}}
          </div>
      </div>
  </div>
</div>
