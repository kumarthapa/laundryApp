@extends('layouts/contentNavbarLayout')

@section('title', 'Edit Product')

@section('content')
    <div class="row">
        <div class="col-md-12 col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-3">Edit Product</h4>
                    <div class="form-text text-warning">
                        Fields with <span class="text-danger">*</span> are mandatory.
                    </div>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('products.save', $product->id) }}" id="product-edit-form"
                        enctype="multipart/form-data">
                        @csrf
                        <div class="row">
                            <!-- Product Name -->
                            <div class="col-md-6 col-12">
                                <div class="mb-3">
                                    <label for="product_name" class="form-label">Product Name <span
                                            class="text-danger">*</span></label>
                                    <input type="text" id="product_name" name="product_name"
                                        class="form-control @error('product_name') is-invalid @enderror"
                                        value="{{ old('product_name', $product->product_name) }}" required>
                                    @error('product_name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <!-- SKU (readonly) -->
                            <div class="col-md-6 col-12">
                                <div class="mb-3">
                                    <label for="qa_code" class="form-label">QA Code</label>
                                    <input type="text" id="qa_code" name="qa_code" class="form-control"
                                        value="{{ $product->qa_code }}" readonly>
                                </div>
                            </div>

                            <!-- SKU (readonly) -->
                            <div class="col-md-6 col-12">
                                <div class="mb-3">
                                    <label for="sku" class="form-label">SKU</label>
                                    <input type="text" id="sku" name="sku" class="form-control"
                                        value="{{ $product->sku }}" readonly>
                                </div>
                            </div>

                            <!-- Reference Code -->
                            <div class="col-md-6 col-12">
                                <div class="mb-3">
                                    <label for="reference_code" class="form-label">Reference Code</label>
                                    <input type="text" id="reference_code" name="reference_code" class="form-control"
                                        value="{{ old('reference_code', $product->reference_code) }}">
                                </div>
                            </div>

                            <!-- Size -->
                            <div class="col-md-6 col-12">
                                <div class="mb-3">
                                    <label for="size" class="form-label">Size <span class="text-danger">*</span></label>
                                    <input type="text" id="size" name="size"
                                        class="form-control @error('size') is-invalid @enderror"
                                        value="{{ old('size', $product->size) }}" required>
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
                                        value="{{ old('quantity', $product->quantity) }}">
                                </div>
                            </div>

                            <!-- RFID Tag (readonly) -->
                            <div class="col-md-6 col-12">
                                <div class="mb-3">
                                    <label for="rfid_tag" class="form-label">RFID Tag</label>
                                    <input type="text" id="rfid_tag" name="rfid_tag" class="form-control"
                                        value="{{ $product->rfid_tag }}" readonly>
                                </div>
                            </div>

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
            $("#product-edit-form").validate({
                rules: {
                    product_name: {
                        required: true,
                        maxlength: 150
                    },
                    size: {
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
                        required: "Please enter the product name",
                        maxlength: "Maximum 150 characters allowed"
                    },
                    size: {
                        required: "Please enter the size",
                        maxlength: "Maximum 150 characters allowed"
                    },
                    quantity: {
                        number: "Please enter a valid number",
                        min: "Quantity cannot be negative"
                    }
                },
                submitHandler: function(form) {
                    $("#submit-button").prop("disabled", true).text("Saving...");

                    var formData = new FormData(form);

                    $.ajax({
                        type: $(form).attr("method"),
                        url: $(form).attr("action"),
                        data: formData,
                        processData: false,
                        contentType: false,
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                        success: function(response) {
                            if (response.success) {
                                toastr.success(response.message ||
                                    "Product updated successfully!");
                                $("#submit-button").text("Saved");
                                setTimeout(function() {
                                    window.location.href = response.return_url ||
                                        "{{ route('products') }}";
                                }, 1500);
                            } else {
                                toastr.error(response.message ||
                                    "Failed to update product.");
                                $("#submit-button").prop("disabled", false).text("Save");
                            }
                        },
                        error: function(xhr) {
                            toastr.error("An error occurred.");
                            $("#submit-button").prop("disabled", false).text("Save");
                        }
                    });
                }
            });
        });
    </script>
@endsection
