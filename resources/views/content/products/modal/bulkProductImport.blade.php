<form method="POST" action="{{ route('products.bulkProductUpload') }}" enctype="multipart/form-data" id="productsImport">
    @csrf
    <div class="modal fade" id="bulkProductImportModal" tabindex="-1" aria-modal="true" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title" id="modalCenterTitle">Bulk Product Upload</h4>
                    <button type="button" class="btn btn-label-danger p-2" data-bs-dismiss="modal" aria-label="Close">
                        <i class='bx bx-x'></i>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="formFile" class="form-label">(xlsx/xls) These Format Are Accepted</label>
                        <input class="form-control" type="file" id="formFile" name="file" required>
                    </div>
                    <div class="mb-3">
                        <div class="demo-inline-spacing">
                            <small class="fw-medium d-block">What action would you like to perform?</small>

                            <div class="form-check form-check-inline mt-4">
                                <input class="form-check-input" type="radio" name="action_type" id="action_upload_new"
                                    value="upload_new" checked>
                                <label class="form-check-label" for="action_upload_new">Upload New Products</label>
                            </div>

                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="action_type"
                                    id="action_update_existing" value="update_existing">
                                <label class="form-check-label" for="action_update_existing">Update Existing
                                    Products</label>
                            </div>
                        </div>
                    </div>

                </div>
                <div class="modal-footer">
                    <a href="javascript:;" class="btn btn-label-primary" id="import-format">
                        Download Upload Format <i class='bx bx-down-arrow-alt'></i>
                    </a>
                    <button type="button" class="btn btn-label-danger" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-label-primary" id="submit-button">Upload</button>
                </div>
            </div>
        </div>
    </div>
</form>

<script>
    $(document).ready(function() {
        $("#productsImport").validate({
            submitHandler: function(form) {
                // Disable button and show loading text
                $("#submit-button").attr('disabled', true).html('Uploading...');

                var formData = new FormData(form);
                $.ajax({
                    url: $(form).attr('action'),
                    type: $(form).attr('method'),
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    data: formData,
                    contentType: false,
                    processData: false,
                    crossDomain: true,
                    async: true,
                    success: function(response) {
                        console.log("response:", response);

                        if (!response.success) {
                            // Show dismissible Bootstrap 5 alert with close button
                            $("#errorBox").html(`
                            <div class="alert alert-danger alert-dismissible" role="alert">
                                <i class='bx bxs-error'></i> ${response.message || 'Something went wrong.'}
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        `);

                            // Make sure errorBox is visible
                            $("#errorBox").removeClass('d-none').show();

                            // Re-enable submit button and reset text
                            $("#submit-button").attr('disabled', false).html('Upload');

                            toastr.error('Product data uploaded failed!');

                            // Hide modal immediately on error
                            var modalEl = document.getElementById(
                                'bulkProductImportModal');
                            var modal = bootstrap.Modal.getInstance(modalEl);
                            if (modal) {
                                $("#formFile").val(''); // Clear file input
                                modal.hide();
                            }
                        } else {
                            // Hide error box and show success toast
                            $("#errorBox").hide();
                            toastr.success(response.message ||
                                'Product data uploaded successfully.');

                            // Disable submit button, update text and redirect after delay
                            $("#submit-button").attr('disabled', true).html('Uploaded');
                            setTimeout(function() {
                                window.location.href =
                                    "{{ route('products') }}";
                            }, 2000);
                        }
                    },


                    error: function(xhr, status, error) {
                        console.log("error:", error);
                        toastr.error('Product data uploaded failed!');
                        if (error === 'Internal Server Error') {
                            $("#errorBox").hide();
                        }
                        // Re-enable button and reset text on error
                        $("#submit-button").attr('disabled', false).html('Upload');
                    }
                });
            }
        });

        $("#import-format").click(function() {
            window.location.href = "{{ route('products.productImportFormat') }}";
        });
    });
</script>
