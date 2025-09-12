<div class="modal fade" id="createPasswordModal" tabindex="-1" aria-modal="true" role="dialog">
    <div class="modal-dialog modal-dialog-centered modal-simple modal-upgrade-plan">
        <div class="modal-content px-2 py-3">
            <div class="modal-header">
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="text-center mb-4">
                    <h3>Update Password</h3>
                    <p>Choose the strong password.</p>
                </div>
                <div class="alert alert-warning" role="alert">
                    <h6 class="alert-heading mb-1">Ensure that these requirements are
                        met</h6>
                    <span>Minimum 6 characters long, uppercase &amp; symbol</span>
                </div>
                <div class="row">
                    <div class="mb-3 col-12 col-sm-6 form-password-toggle">
                        <label class="form-label" for="newPassword">New Password</label>
                        <div class="input-group input-group-merge">
                            <input class="form-control" type="password" id="newPassword" name="newPassword"
                                placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;">
                            <span class="input-group-text cursor-pointer" id="passwordViewIcon"><i
                                    class="bx bx-show"></i></span>
                        </div>
                        <div class="invalid-feedback">
                            Password is required.
                        </div>
                    </div>
                    <div class="mb-3 col-12 col-sm-6 form-password-toggle">
                        <label class="form-label" for="confirmPassword">Confirm New Password</label>
                        <div class="input-group input-group-merge">
                            <input class="form-control" type="password" name="confirmPassword" id="confirmPassword"
                                placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;">
                            <span class="input-group-text cursor-pointer" id="confirmPasswordViewIcon"><i
                                    class="bx bx-hide"></i></span>
                        </div>
                        <div class="invalid-feedback">
                            Passwords do not match.
                        </div>
                    </div>
                    <div class="mb-3 col-12" id="PasswordMatchMessage" style="display: none;">
                        <div class="alert alert-success" role="alert">
                            <span>Password match successfully!</span>
                        </div>
                    </div>

                    <div class="text-end">
                        <button type="button" class="btn btn-primary me-2" onclick="validatePasswords()">Done</button>
                    </div>
                </div>




            </div>
            {{-- <hr class="mx-md-n5 mx-n3"> --}}
        </div>
    </div>
</div>
<script>
    document.getElementById('passwordViewIcon').addEventListener('click', function() {
        const passwordField = document.getElementById('newPassword');
        if (passwordField.type === 'password') {
            passwordField.type = 'text';
            this.querySelector('i').classList.replace('bx-show', 'bx-hide');
        } else {
            passwordField.type = 'password';
            this.querySelector('i').classList.replace('bx-hide', 'bx-show');
        }
    });

    document.getElementById('confirmPasswordViewIcon').addEventListener('click', function() {
        const confirmPasswordField = document.getElementById('confirmPassword');
        if (confirmPasswordField.type === 'password') {
            confirmPasswordField.type = 'text';
            this.querySelector('i').classList.replace('bx-hide', 'bx-show');
        } else {
            confirmPasswordField.type = 'password';
            this.querySelector('i').classList.replace('bx-show', 'bx-hide');
        }
    });

    function validatePasswords() {
        const newPassword = document.getElementById('newPassword');
        const confirmPassword = document.getElementById('confirmPassword');
        const newPasswordValue = newPassword.value;
        const confirmPasswordValue = confirmPassword.value;
        const newPasswordContainer = newPassword.closest('.form-password-toggle');
        const confirmPasswordContainer = confirmPassword.closest('.form-password-toggle');

        let isValid = true;

        if (!newPasswordValue) {
            newPasswordContainer.classList.add('is-invalid');
            isValid = false;
        } else {
            if (newPasswordValue.length < 6) {
                alert("Password must be minimum 6 characters long");
                return false;
            };
            newPasswordContainer.classList.remove('is-invalid');
        }

        if (newPasswordValue !== confirmPasswordValue) {
            confirmPasswordContainer.classList.add('is-invalid');
            isValid = false;
        } else {
            confirmPasswordContainer.classList.remove('is-invalid');
        }

        if (isValid) {
            document.getElementById('PasswordMatchMessage').style.display = 'block';
            // console.log(confirmPasswordValue)
            setTimeout(function() {
                $("#updatePassword").modal('hide');
                document.getElementById('PasswordMatchMessage').style.display = 'none';
            }, 1000);

            // alert('Passwords match!');
        }
    }
</script>
