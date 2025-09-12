    {{-- Documents Upload Modal --}}
    @php
        $pending_docs = App\Helpers\UtilityHelper::fetchNotUploadedDocuments($config_key, $collection);
    @endphp
    <div class="modal fade" id="common_docs_upload_modal" tabindex="-1" aria-modal="true" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title" id="modalCenterTitle">Upload Required Documets
                        <p class="py-0 my-0" style="font-size: 15px;">Valid Extension (jpg, jpeg, png, pdf)</p>
                    </h4>
                    <button type="button" class="btn btn-label-danger p-2" data-bs-dismiss="modal" aria-label="Close">
                        <i class='bx bx-x'></i>
                    </button>
                </div>
                <form class="needs-validation uploadRequiredDocuments" novalidate id="pendingDocumentsUpload">
                    {{ csrf_field() }}
                    <input type="hidden" name="code" value="{{ $code }}">
                    <input type="hidden" name="config_key" value="{{ $config_key }}">
                    <input type="hidden" name="folder_name" value="{{ $folder_name }}">
                    <div class="modal-body">
                        <hr class="my-4 mx-n4">
                        <div class="row g-2">
                            <h5>*Required Documets</h5>
                            @if (isset($pending_docs) && count($pending_docs))
                                @foreach ($pending_docs as $key => $document)
                                    <?php
                                    $is_required = "";
                                    if (isset($document["is_required"]) && $document["is_required"]) {
                                        $is_required = "required";
                                    }
                                    ?>
                                    <div class="mb-2 col-md-6 card col-12 p-3" {{ $key }}>
                                        <div class="row">
                                            <div class="col-md-12">
                                                <label for="{{ $document["docs_type"] }}_number"
                                                    class="form-label">{{ $document["label_name"] }}</label>
                                                <input class="form-control {{ $document["docs_type"] }}_number"
                                                    type="text" id="{{ $document["docs_type"] }}_number"
                                                    name="{{ $document["docs_type"] }}_number"
                                                    placeholder="Enter {{ $document["label_name"] }}"
                                                    {{ $is_required }}>
                                            </div>
                                            @if (isset($document["start_date"]) && $document["start_date"])
                                                <div class="col-md-6 col-12 py-2">
                                                    <label for="Registration Date" class="form-label">Registration
                                                        Date</label>
                                                    <input class="form-control {{ $document["docs_type"] }}_start"
                                                        type="date" placeholder="mm/dd/yy"
                                                        name="{{ $document["docs_type"] }}_start"
                                                        id="{{ $document["docs_type"] }}_start" {{ $is_required }} />
                                                </div>
                                            @endif
                                            @if (isset($document["has_expiry"]) && $document["has_expiry"])
                                                <div class="col-md-6 col-12 py-2">
                                                    <label for="Expiry Date" class="form-label">Expiry
                                                        Date</label>
                                                    <input class="form-control {{ $document["docs_type"] }}_expiry"
                                                        type="date" placeholder="mm/dd/yy"
                                                        name="{{ $document["docs_type"] }}_expiry"
                                                        id="{{ $document["docs_type"] }}_expiry"
                                                        {{ $is_required }} />
                                                </div>
                                            @endif

                                            <div class="d-flex align-items-start align-items-sm-center gap-4 py-2">
                                                <div class="button-wrapper w-100">
                                                    <label for="{{ $document["docs_type"] }}_images"
                                                        class="w-100 me-2 mb-4" tabindex="0">
                                                        <span class="d-none d-sm-block">Upload {{ $document["name"] }}
                                                            photo</span>
                                                        <i class="bx bx-upload d-block d-sm-none"></i>
                                                        <input type="file" id="{{ $document["docs_type"] }}_images"
                                                            name="{{ $document["docs_type"] }}_images[]"
                                                            class="account-file-input form-control {{ $document["docs_type"] }}_images"
                                                            multiple>
                                                    </label>
                                                    {{-- <p class="text-muted mb-0">Allowed JPG, GIF or PNG. Max size of 800K</p> --}}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            @endif
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
