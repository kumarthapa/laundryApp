<div class="card mb-3">
    <h4 class="card-header"><i class='bx bxs-buildings'></i> Company Info</h4>
    <div class="card-body">
        <p>
            This information about our company can be utilized universally across our entire website
        </p>
        <form class="needs-validation save_setting_data" novalidate id="save_company_details">
            {{ csrf_field() }}
            <input type="hidden" name="submit_form_name" value="save_company_details">
            <div class="row mb-3">
                <label for="company_name" class="col-md-2 col-form-label">Company Name</label>
                <div class="col-md-10">
                    <input class="form-control" type="text" placeholder="Enter Company Name" name="company_name"
                        id="company_name" value="{{ $UtilityHelper->getConfigValue("company_name") }}" required />
                    <div class="invalid-feedback"> This field is required. </div>
                </div>
            </div>
            <div class="row mb-3">
                <label for="gst_number" class="col-md-2 col-form-label">Pan Number</label>
                <div class="col-md-10">
                    <input class="form-control" type="text" placeholder="Enter Pan Number" name="company_pan_number"
                        id="company_pan_number" value="{{ $UtilityHelper->getConfigValue("company_pan_number") }}" />
                    <div class="invalid-feedback"> This field is required. </div>
                </div>
            </div>
            <div class="row mb-3">
                <label for="gst_number" class="col-md-2 col-form-label">GST Number</label>
                <div class="col-md-10">
                    <input class="form-control" type="text" placeholder="Enter GST Number" name="company_gst_number"
                        id="company_gst_number" value="{{ $UtilityHelper->getConfigValue("company_gst_number") }}" />
                    <div class="invalid-feedback"> This field is required. </div>
                </div>
            </div>
            <div class="row mb-3">
                <label for="company_email" class="col-md-2 col-form-label">Email</label>
                <div class="col-md-10">
                    <input class="form-control" type="email" placeholder="Enter Email" name="company_email"
                        id="company_email" value="{{ $UtilityHelper->getConfigValue("company_email") }}" />
                    <div class="invalid-feedback"> This field is required. </div>
                </div>
            </div>
            <div class="row mb-3">
                <label for="company_phone" class="col-md-2 col-form-label">Phone</label>
                <div class="col-md-10">
                    <input class="form-control" type="tel" placeholder="Enter Phone" name="company_phone"
                        id="company_phone" value="{{ $UtilityHelper->getConfigValue("company_phone") }}" />
                    <div class="invalid-feedback"> This field is required. </div>
                </div>
            </div>
            <div class="row mb-3">
                <label for="company_address" class="col-md-2 col-form-label">Address</label>
                <div class="col-md-10">
                    <textarea class="form-control" id="company_address" rows="3" placeholder="Enter Full address"
                        name="company_address">{{ $UtilityHelper->getConfigValue("company_address") }}</textarea>
                    <div class="invalid-feedback"> This field is required. </div>
                </div>
            </div>

            <div class="row mb-3">
                <label for="City" class="col-md-2 col-form-label">City</label>
                <div class="col-md-10">
                    <input class="form-control" type="text" placeholder="Enter City" name="company_city"
                        id="company_city" value="{{ $UtilityHelper->getConfigValue("company_city") }}" />
                    <div class="invalid-feedback"> This field is required. </div>
                </div>
            </div>
            <div class="row mb-3">
                <label for="company_pincode" class="col-md-2 col-form-label">Pincode</label>
                <div class="col-md-10">
                    <input class="form-control" type="text" placeholder="Enter Pincode" name="company_pincode"
                        id="company_pincode" value="{{ $UtilityHelper->getConfigValue("company_pincode") }}" />
                    <div class="invalid-feedback"> This field is required. </div>
                </div>
            </div>
            <div class="row mb-3">
                <label for="company_state" class="col-md-2 col-form-label">State</label>
                <div class="col-md-10">
                    <input class="form-control" type="text" placeholder="Enter State" name="company_state"
                        id="company_state" value="{{ $UtilityHelper->getConfigValue("company_state") }}" />
                    <div class="invalid-feedback"> This field is required. </div>
                </div>
            </div>

            <div class="row mb-3">
                <label for="company_brand_name" class="col-md-2 col-form-label">Brand Name</label>
                <div class="col-md-10">
                    <input class="form-control" type="text" placeholder="Enter Brand Name"
                        name="company_brand_name" id="company_brand_name"
                        value="{{ $UtilityHelper->getConfigValue("company_brand_name") }}" />
                    <div class="invalid-feedback"> This field is required. </div>
                </div>
            </div>

            <div class="row mb-3">
                <label for="company logo" class="col-md-2 col-form-label">Company Logo</label>
                <div class="col-md-6 col-12">
                    <div class="d-flex justify-content-start align-items-center">
                        @if ($UtilityHelper->getConfigValue("company_logo"))
                            <div class="ms-2">
                                <label for="company logo" class="col-form-label">Company Logo</label>
                                <div class="d-flex align-items-start align-items-sm-center">
                                    <div class="card">
                                        <div class="card-body p-2">
                                            <img class="card-img-top p-1"
                                                src="{{ asset($UtilityHelper->getConfigValue("company_logo")) }}"
                                                title="Company logo" alt="Company logo">
                                            <a href="#uploadsCompanyLogo" data-bs-toggle="modal"role="button">
                                                <div class="avatar avatar-sm">
                                                    <span
                                                        class="avatar-initial bg-label-primary rounded-circle pull-up"><i
                                                            class='bx bxs-edit-alt'></i></span>
                                                </div>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @else
                            <div class="ms-2">
                                <label for="company logo" class="col-form-label">Company Logo</label>
                                <div class="card p-3 text-center">
                                    <div class="card-header">
                                    </div>
                                    <div class="card-body">
                                        <a class="btn btn-label-primary" href="#uploadsCompanyLogo"
                                            data-bs-toggle="modal"role="button">
                                            <i class='bx bx-upload fs-6'></i></span>
                                        </a>
                                    </div>
                                    <div class="card-footer text-muted">

                                    </div>
                                </div>
                            </div>
                        @endif


                        @if ($UtilityHelper->getConfigValue("company_brand_logo"))
                            <div class="ms-2">
                                <label for="company brand logo" class="col-form-label">Brand Logo</label>
                                <div class="d-flex align-items-start align-items-sm-center">
                                    <div class="card">
                                        <div class="card-body p-2">
                                            <img class="card-img-top p-1"
                                                src="{{ asset($UtilityHelper->getConfigValue("company_brand_logo")) }}"
                                                title="company_brand_logo" alt="company_brand_logo">
                                            <a href="#uploadsCompanyLogo" data-bs-toggle="modal"role="button">
                                                <div class="avatar avatar-sm">
                                                    <span
                                                        class="avatar-initial bg-label-primary rounded-circle pull-up"><i
                                                            class='bx bxs-edit-alt'></i></span>
                                                </div>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @else
                            <div class="ms-2">
                                <label for="company brand logo" class="col-form-label">Brand Logo</label>
                                <div class="card p-2 text-center">
                                    <div class="card-header">
                                    </div>
                                    <div class="card-body">
                                        <a class="btn btn-label-primary" href="#uploadsCompanyLogo"
                                            data-bs-toggle="modal"role="button">
                                            <i class='bx bx-upload fs-6'></i></span>
                                        </a>
                                    </div>
                                    <div class="card-footer text-muted">

                                    </div>
                                </div>
                            </div>
                        @endif

                    </div>
                </div>
            </div>
            <div class="row mb-3">
                <label for="company_terms_and_conditions" class="col-md-2 col-form-label">Terms & Conditions</label>
                <div class="col-md-10">
                    <textarea class="form-control" id="company_terms_and_conditions" rows="3"
                        placeholder="Enter Terms & Conditions" name="company_terms_and_conditions">{{ $UtilityHelper->getConfigValue("company_terms_and_conditions") }}</textarea>
                    <div class="invalid-feedback"> This field is required. </div>
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-md-12 text-end">
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </div>
        </form>
    </div>
</div>
