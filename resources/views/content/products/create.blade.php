@extends('layouts/contentNavbarLayout')

@section('title', 'Create Product')

@section('content')
    <div class="row">
        <div class="col-md-12 col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="d-flex mb-1">Create New Product</h4>
                    <div class="d-flex justify-content-start form-label-class">
                        <p class="card-subtitle text-warning my-0">Fields marked with <span class="text-danger">*</span> are
                            mandatory</p>
                    </div>
                </div>

                <div class="card-body">
                    <form method="POST" action="{{ route('products.save') }}" id="product-create-form"
                        enctype="multipart/form-data">
                        @csrf
                        <div class="row">

                            <!-- Product Name -->
                            <div class="col-md-6 col-12">
                                <div class="mb-3">
                                    <label for="product_name" class="form-label">Product Name <span
                                            class="text-danger">*</span></label>
                                    <input type="text" id="product_name" name="product_name"
                                        class="form-control @error('product_name') is-invalid @enderror" required
                                        value="{{ old('product_name') }}">
                                    @error('product_name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <!-- SKU -->
                            <div class="col-md-6 col-12">
                                <div class="mb-3">
                                    <label for="sku" class="form-label">SKU <span class="text-danger">*</span></label>
                                    <input type="text" id="sku" name="sku"
                                        class="form-control @error('sku') is-invalid @enderror" required
                                        value="{{ old('sku') }}">
                                    @error('sku')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <!-- Reference Code -->
                            <div class="col-md-6 col-12">
                                <div class="mb-3">
                                    <label for="reference_code" class="form-label">Reference Code</label>
                                    <input type="text" id="reference_code" name="reference_code" class="form-control"
                                        value="{{ old('reference_code') }}">
                                </div>
                            </div>

                            <!-- Size -->
                            <div class="col-md-6 col-12">
                                <div class="mb-3">
                                    <label for="size" class="form-label">Size <span class="text-danger">*</span></label>
                                    <input type="text" id="size" name="size"
                                        class="form-control @error('size') is-invalid @enderror" required
                                        value="{{ old('size') }}">
                                    @error('size')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>


                            <!-- Quantity -->
                            <div class="col-md-6 col-12">
                                <div class="mb-3">
                                    <label for="quantity" class="form-label">Quantity</label>
                                    <input type="number" id="quantity" name="quantity" min="0" class="form-control"
                                        value="{{ old('quantity', 0) }}">
                                </div>
                            </div>

                            <!-- RFID Tag -->
                            <div class="col-md-6 col-12">
                                <div class="mb-3">
                                    <label for="rfid_tag" class="form-label">RFID Tag <span
                                            class="text-danger">*</span></label>
                                    <div class="row">
                                        <div class="col-md-8 col-12">
                                            <input type="text" id="rfid_tag" name="rfid_tag"
                                                class="form-control @error('rfid_tag') is-invalid @enderror" required
                                                value="{{ old('rfid_tag') }}">
                                            @error('rfid_tag')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="col-md-4 col-12">
                                            <button type="button" class="btn btn-label-primary w-100 mt-md-0 mt-2"
                                                onclick="autoGenerateRFIDTag()">Auto Generate Tag
                                            </button>
                                        </div>
                                    </div>


                                </div>
                            </div>


                            <!-- QC Status -->
                            {{-- <div class="col-md-6 col-12">
                                <div class="mb-3">
                                    <label for="qc_status" class="form-label">QC Status</label>
                                    <select id="qc_status" name="qc_status" class="form-select">
                                        <option value="PENDING" {{ old("qc_status") === "PENDING" ? "selected" : "" }}>
                                            PENDING</option>
                                        <option value="PASS" {{ old("qc_status") === "PASS" ? "selected" : "" }}>PASS
                                        </option>
                                        <option value="FAILED" {{ old("qc_status") === "FAILED" ? "selected" : "" }}>FAILED
                                        </option>
                                    </select>
                                </div>
                            </div> --}}

                        </div>
                        <div class="my-3 text-end">
                            <button type="submit" class="btn btn-primary" id="submit-button">Save</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('page-script')
    <script>
        $(document).ready(function() {
            // Setup jQuery validation for product form
            $("#product-create-form").validate({
                rules: {
                    product_name: {
                        required: true,
                        maxlength: 150
                    },
                    sku: {
                        required: true,
                        maxlength: 100
                    },
                    size: {
                        required: true,
                        maxlength: 150
                    },
                    rfid_tag: {
                        required: true,
                        maxlength: 150
                    },
                    quantity: {
                        number: true,
                        min: 0
                    }
                },
                messages: {
                    product_name: {
                        required: 'Please enter product name',
                        maxlength: 'Product name cannot exceed 150 characters'
                    },
                    sku: {
                        required: 'Please enter SKU',
                        maxlength: 'SKU cannot exceed 100 characters'
                    },
                    size: {
                        required: 'Please enter size',
                        maxlength: 'Size cannot exceed 150 characters'
                    },
                    rfid_tag: {
                        required: 'Please enter RFID tag',
                        maxlength: 'RFID tag cannot exceed 150 characters'
                    },
                    quantity: {
                        number: 'Quantity must be a number',
                        min: 'Quantity cannot be negative'
                    }
                },
                submitHandler: function(form) {
                    // Disable submit button and show loading text
                    $("#submit-button").attr('disabled', true).html('Saving...');

                    var formData = new FormData(form);

                    $.ajax({
                        url: $(form).attr('action'),
                        type: $(form).attr('method'),
                        data: formData,
                        contentType: false,
                        processData: false,
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                        success: function(response) {
                            if (response.success) {
                                toastr.success(response.message ||
                                    'Product saved successfully');
                                $("#submit-button").html('Saved');
                                setTimeout(function() {
                                    // Redirect or reload page
                                    window.location.href = response.return_url ||
                                        "{{ route('products') }}";
                                }, 1500);
                            } else {
                                $("#submit-button").attr('disabled', false).html(
                                    'Save');
                                toastr.error(response.message || 'Failed to save product');
                            }
                        },
                        error: function(xhr) {
                            $("#submit-button").attr('disabled', false).html(
                                'Save');
                            var errors = xhr.responseJSON?.errors;
                            if (errors) {
                                // Show validation errors returned from server
                                $.each(errors, function(key, val) {
                                    toastr.error(val[0]);
                                });
                            } else {
                                toastr.error('An unexpected error occurred');
                            }
                        }
                    });
                }
            });
        });
        // Function to auto-generate a random RFID tag
        function autoGenerateRFIDTag() {
            let productName = $('#product_name').val().trim();
            let sku = $('#sku').val().trim();
            let size = $('#size').val().trim();
            if (!productName || !sku || !size) {
                toastr.warning('Please enter Product Name, SKU and Size before generating RFID tag');
                return;
            }
            // Simple random tag generation logic (can be customized as needed)
            let randomTag = productName.substring(0, 3).toUpperCase() + '-' +
                sku.substring(0, 3).toUpperCase() + '-' +
                size.substring(0, 3).toUpperCase() + '-' +
                Math.random().toString(36).substring(2, 8).toUpperCase();
            $('#rfid_tag').val(randomTag);

        }
    </script>
@endsection
