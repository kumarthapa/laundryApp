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
                                    <label for="sku" class="form-label">SKU</label>
                                    <input type="text" id="sku" name="sku" class="form-control"
                                        value="{{ $product->sku }}" readonly>
                                </div>
                            </div>

                            <!-- Description -->
                            <div class="col-md-6 col-12">
                                <div class="mb-3">
                                    <label for="description" class="form-label">Description</label>
                                    <input type="text" id="description" name="description" class="form-control"
                                        value="{{ old('description', $product->description) }}">
                                </div>
                            </div>
                            <!-- QC Status -->
                            <div class="col-md-6 col-12">
                                <div class="mb-3">
                                    <label for="qc_status" class="form-label">QC Status</label>
                                    <select id="qc_status" name="qc_status" class="form-select">
                                        <option value="1">
                                            Active</option>
                                        <option value="0">Inactive
                                        </option>
                                    </select>
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
