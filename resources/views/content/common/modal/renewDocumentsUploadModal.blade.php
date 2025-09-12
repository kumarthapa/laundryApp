<div class="modal fade" id="common_renewdocs_upload_modal" tabindex="-1" aria-modal="true" role="dialog">
    <div class="modal-dialog modal-dialog-centered modal-md">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="modalCenterTitle">Upload Required Documets<p class="py-0 my-0"
                        style="font-size: 15px;">Valid Extension (jpg, jpeg, png, pdf)</p>
                </h4>
                <button type="button" class="btn btn-label-danger p-2" data-bs-dismiss="modal" aria-label="Close">
                    <i class='bx bx-x'></i>
                </button>
            </div>
            <form class="needs-validation uploadRequiredDocuments" novalidate id="renewDocsUploadForm">
                {{ csrf_field() }}
                <div class="modal-body">
                    <hr class="my-4 mx-n4">
                    <div class="row g-2">
                        <h5>*Required Documets</h5>
                        <div class="renew_docs_div">

                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-label-danger" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" id="submit-button" class="btn btn-label-primary">Upload</button>
                </div>
            </form>
        </div>
    </div>
</div>
