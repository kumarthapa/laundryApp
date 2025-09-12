            <!-- Offcanvas to add new user -->
            <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasAddrole"
                aria-labelledby="offcanvasAddroleLabel">
                <div class="offcanvas-header">
                    <h5 id="offcanvasAddroleLabel" class="offcanvas-title">Add User</h5>
                    <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas"
                        aria-label="Close"></button>
                </div>
                <div class="offcanvas-body mx-0 flex-grow-0">
                            <!-- Add user form -->
                {!! Form::open(
                    ['url' => route('users.save'), 'id' => 'userStoreForm', 'method' => 'POST','novalidate' => 'novalidate','method' => 'onsubmit','return false' => 'add-new-user pt-0 fv-plugins-bootstrap5 fv-plugins-framework',],
                    csrf_field(),
                ) !!}

                        <div class="mb-3 fv-plugins-icon-container">
                            <label class="form-label" for="add-user-firstname">First Name</label>
                            <input type="text" class="form-control" id="userFirstName" placeholder="First Name"
                                name="userFirstName" aria-label="First Name">
                            <div class="fv-plugins-message-container fv-plugins-message-container--enabled invalid-feedback">
                            </div>
                        </div>
                        <div class="mb-3 fv-plugins-icon-container">
                            <label class="form-label" for="add-user-lastname">Last Name</label>
                            <input type="text" class="form-control" id="userLastName" placeholder="Last name"
                                name="userLastName" aria-label="Last name">
                            <div
                                class="fv-plugins-message-container fv-plugins-message-container--enabled invalid-feedback">
                            </div>
                        </div>
                        <div class="mb-3 fv-plugins-icon-container">
                            <label class="form-label" for="add-user-email">Email</label>
                            <input type="text" id="userEmail" class="form-control"
                                placeholder="john.doe@example.com" aria-label="john.doe@example.com" name="userEmail">
                            <div
                                class="fv-plugins-message-container fv-plugins-message-container--enabled invalid-feedback">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="add-user-contact">Contact</label>
                            <input type="text" id="add-user-contact" class="form-control phone-mask"
                                placeholder="+1 (609) 988-44-11" aria-label="john.doe@example.com" name="userContact">
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="user-role">User Role</label>
                            <select id="user-role" class="form-select" name="user_role_id">
                                <option value="">Select Role</option>
                                @if(isset($roles_info) && $roles_info)
                                @foreach($roles_info as $roles)
                                <option value="{{ $roles->role_id }}">{{ $roles->role_name }}</option>
                                @endforeach
                                @endif
                            </select>
                        </div>
        
                            <div class="mb-3 form-password-toggle fv-plugins-icon-container">
                                <label class="form-label" for="userPassWord">Password</label>
                                <div class="input-group input-group-merge">
                                  <input type="password" class="form-control" id="userPassWord" name="userPassWord" placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;" 
                                  aria-describedby="userPassWord" />
                                  <span class="input-group-text cursor-pointer"><i class="bx bx-hide"></i></span>
                                </div>
                              </div>
                    
        
                            <div class="mb-3 form-password-toggle fv-plugins-icon-container">
                                <label class="form-label" for="confirmPassWord">Confirm Password</label>
                                <div class="input-group input-group-merge">
                                  <input type="password" class="form-control" id="confirmPassWord" placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;" 
                                  aria-describedby="confirmPassWord" />
                                  <span class="input-group-text cursor-pointer"><i class="bx bx-hide"></i></span>
                                </div>
                            </div>
        
                        <div class="mb-4">
                            <label class="col-sm-3 col-form-label" for="Status">Status</label>
                            <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" value="1" name="status" id="status" checked>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary me-sm-3 me-1 data-submit">Submit</button>
                        <button type="reset" class="btn btn-label-secondary"
                            data-bs-dismiss="offcanvas">Cancel</button>
                        <input type="hidden">
                        {{ Form::close() }}
                </div>
            </div>

            {{-- <script>
            $(document).ready(function() {

            });
            </script> --}}