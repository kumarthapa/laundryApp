{{-- Add New Vehicle modal --}}
<div class="modal fade" id="uploadsCompanyLogo" tabindex="-1" aria-modal="true" role="dialog">
    <div class="modal-dialog modal-dialog-centered modal-md modal-dialog-scrollable">
        <div class="modal-content p-md-2">
            <div class="modal-header">
                <h4 class="modal-title">Upload Company Logo</h4>
                <button type="button" class="btn btn-label-danger p-2" data-bs-dismiss="modal" aria-label="Close">
                    <i class='bx bx-x'></i>
                </button>
            </div>
            <div class="modal-body">
                <form class="needs-validation save_setting_data" novalidate id="save_company_docs">
                    {{ csrf_field() }}
                    <input type="hidden" name="submit_form_name" value="save_company_details">
                    <div class="mb-3 row">
                        <label for="company_logo" class="col-md-12 col-form-label">Upload Comapny Logo
                        </label>
                        <div class="col-md-12 col-12">
                            <div class="d-flex align-items-start align-items-sm-center gap-4 py-2">
                                <div class="button-wrapper w-100">
                                    <label for="company_logo" class="w-100 me-2 mb-4" tabindex="0">
                                        <i class="bx bx-upload d-block d-sm-none"></i>
                                        <input type="file" id="company_logo" name="logo_upload[company_logo]"
                                            class="account-file-input form-control">
                                    </label>
                                    <p class="text-muted mb-0">Allowed JPG,JPEG and PNG. Max size of 2048</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3 row">
                        <label for="company_brand_logo" class="col-md-12 col-form-label">Upload Brand Logo
                        </label>
                        <div class="col-md-12 col-12">
                            <div class="d-flex align-items-start align-items-sm-center gap-4 py-2">
                                <div class="button-wrapper w-100">
                                    <label for="company_brand_logo" class="w-100 me-2 mb-4" tabindex="0">
                                        <i class="bx bx-upload d-block d-sm-none"></i>
                                        <input type="file" id="company_brand_logo"
                                            name="logo_upload[company_brand_logo]"
                                            class="account-file-input form-control">
                                    </label>
                                    <p class="text-muted mb-0">Allowed JPG,JPEG and PNG. Max size of 2048</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-12 text-end pt-3">
                        <button type="submit" class="btn btn-primary">Upload</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
