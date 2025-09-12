<div class="modal fade" id="trips_import_modal" tabindex="-1" aria-modal="true" role="dialog">
    <form method="POST" action="{{ route("trips.import") }}" enctype="multipart/form-data" id="trips-import-form">
        @csrf
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title" id="modalCenterTitle">Bulk Import Trips</h4>
                    <button type="button" class="btn btn-label-danger p-2" data-bs-dismiss="modal" aria-label="Close">
                        <i class='bx bx-x'></i>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="formFile" class="form-label">Upload File</label>
                        <input class="form-control" type="file" id="formFile" name="file" required>
                    </div>

                    <div class="col-md p-6">
                        <label for="formFile" class="form-label">Existing rows will be ?</label>
                        <div class="form-check">
                            <input name="skip_update" class="form-check-input" type="radio" value="skip" checked>
                            <label class="form-check-label" for="defaultRadio1">
                                Skip
                            </label>
                        </div>
                        <div class="form-check">
                            <input name="skip_update" class="form-check-input" type="radio" value="update">
                            <label class="form-check-label" for="defaultRadio2">
                                Update
                            </label>
                        </div>
                    </div>
                    {{-- ------------------------------------- Error message ------------------------------------------ --}}
                    <div class="mb-3">
                        <div class="alert alert-danger" role="alert" id="errorBox" style="display: none;">
                        </div>
                    </div>
                    <div class="result"></div>
                </div>
                <div class="modal-footer">
                    {{-- <a href="javascript:;" class="btn btn-label-primary" id="import-format">Download Import Format <i
                            class='bx bx-down-arrow-alt'></i>
                    </a> --}}
                    <button type="button" class="btn btn-label-danger" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-label-primary" id="submit-button">Import</button>
                </div>
            </div>

        </div>
    </form>
</div>
