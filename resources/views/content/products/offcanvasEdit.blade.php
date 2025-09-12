<div class="offcanvas offcanvas-end" id="productEditCanvas" tabindex="-1" aria-labelledby="productEditLabel">
    <div class="offcanvas-header">
        <h5 id="productEditLabel" class="offcanvas-title">Edit Product</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body" id="productEditContent">
        <form method="POST" action="{{ route("products.save", $product->id) }}" id="product-edit-form"
            enctype="multipart/form-data">
            @csrf
            @method("PUT")

            <div class="mb-3">
                <label for="product_name" class="form-label">Product Name <span class="text-danger">*</span></label>
                <input type="text" id="product_name" name="product_name"
                    class="form-control @error("product_name") is-invalid @enderror"
                    value="{{ old("product_name", $product->product_name) }}" required>
                @error("product_name")
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label for="sku" class="form-label">SKU</label>
                <input type="text" id="sku" name="sku" class="form-control" value="{{ $product->sku }}"
                    readonly>
            </div>

            <div class="mb-3">
                <label for="reference_code" class="form-label">Reference Code</label>
                <input type="text" id="reference_code" name="reference_code" class="form-control"
                    value="{{ old("reference_code", $product->reference_code) }}">
            </div>

            <div class="mb-3">
                <label for="size" class="form-label">Size <span class="text-danger">*</span></label>
                <input type="text" id="size" name="size"
                    class="form-control @error("size") is-invalid @enderror" value="{{ old("size", $product->size) }}"
                    required>
                @error("size")
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label for="quantity" class="form-label">Quantity</label>
                <input type="number" id="quantity" name="quantity" min="0" class="form-control"
                    value="{{ old("quantity", $product->quantity) }}">
            </div>

            <div class="mb-3">
                <label for="rfid_tag" class="form-label">RFID Tag</label>
                <input type="text" id="rfid_tag" name="rfid_tag" class="form-control"
                    value="{{ $product->rfid_tag }}" readonly>
            </div>

            <div class="text-end">
                <button type="submit" id="submit-button" class="btn btn-primary">Save</button>
            </div>
        </form>

    </div>
</div>
