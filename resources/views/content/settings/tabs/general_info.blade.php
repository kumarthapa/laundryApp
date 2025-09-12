<div class="card mb-3">
    <h4 class="card-header"><span><i class="bx bx-check-shield"></i></span> General Information</h4>
    <form class="needs-validation" onsubmit="return false" novalidate>
        {{ csrf_field() }}
        <div class="card-body py-2">
            <p>Display content from your connected accounts on your site</p>
            <div class="d-flex py-2">
                <div class="flex-shrink-0">
                    <img src="https://demos.themeselection.com/sneat-bootstrap-html-laravel-admin-template/demo/assets/img/icons/brands/asana.png"
                        alt="asana" class="me-3" height="30">
                </div>
                <div class="flex-grow-1 row">
                    <div class="col-9 mb-sm-0 mb-2">
                        <h6 class="mb-0">Allow All Modules</h6>
                        <small class="text-muted">Communication</small>
                    </div>
                    <input type="hidden" name="save_type" value="save_general_info">
                    <div class="col-3 text-end">
                        <label class="switch me-0">
                            <input type="checkbox" class="switch-input" name="allow_all_module" value="1" required>
                            <span class="switch-toggle-slider">
                                <span class="switch-on">
                                    <i class="bx bx-check"></i>
                                </span>
                                <span class="switch-off">
                                    <i class="bx bx-x"></i>
                                </span>
                            </span>
                            <span class="switch-label"></span>
                        </label>
                        <div class="invalid-feedback"> Please enter your name. </div>
                    </div>

                </div>
            </div>

            <div class="d-flex py-2">
                <div class="flex-shrink-0">
                    <img src="https://demos.themeselection.com/sneat-bootstrap-html-laravel-admin-template/demo/assets/img/icons/brands/asana.png"
                        alt="asana" class="me-3" height="30">
                </div>
                <div class="flex-grow-1 row">
                    <div class="col-9 mb-sm-0 mb-2">
                        <h6 class="mb-0">Allow all navigation systems</h6>
                        <small class="text-muted">Communication</small>
                    </div>
                    <input type="hidden" name="save_type" value="save_general_info">
                    <div class="col-3 text-end">
                        <label class="switch me-0">
                            <input type="checkbox" class="switch-input" name="allow_all_module" value="1" required>
                            <span class="switch-toggle-slider">
                                <span class="switch-on">
                                    <i class="bx bx-check"></i>
                                </span>
                                <span class="switch-off">
                                    <i class="bx bx-x"></i>
                                </span>
                            </span>
                            <span class="switch-label"></span>
                        </label>
                        <div class="invalid-feedback"> Please enter your name. </div>
                    </div>

                </div>
            </div>
        </div>
        <div class="card-footer text-end">
            <button type="submit" class="btn btn-primary me-2">Save changes</button>
        </div>
    </form>
</div>
