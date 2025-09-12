<hr class="my-4">
<div class="row g-2">
    <h4><i class="bx bxs-file-doc"></i> {{ isset($title) ? $title : "Required Documets" }} (Valid Extension - jpg,jpeg,
        png,
        pdf)
    </h4>
    @if (isset($docs) && count($docs) > 0)
        @foreach ($docs as $key => $document)
            <?php
            $clasS = "col-md-6 card col-12 mb-2 p-3";
            $is_required = "";
            if (isset($document["is_required"]) && $document["is_required"] == 1) {
                $is_required = "required";
            }
            if (count($docs) == 1) {
                $clasS = "col-md-12 card col-12 mb-2 p-3";
            } else {
                $clasS = "col-md-6 card col-12 mb-2 p-3";
            }
            ?>
            <div class="{{ $clasS }}">
                <div class="row">
                    <div class="col-md-12">
                        <label for="{{ $document["docs_type"] }}_number"
                            class="form-label">{{ $document["label_name"] }}</label>
                        <input class="form-control {{ $document["docs_type"] }}_number" type="text"
                            id="{{ $document["docs_type"] }}_number" name="{{ $document["docs_type"] }}_number"
                            placeholder="Enter {{ $document["label_name"] }}" {{ $is_required }}>
                    </div>
                    @if (isset($document["start_date"]) && $document["start_date"])
                        <div class="col-md-6 col-12 py-2">
                            <label for="Registration Date" class="form-label">Registration
                                Date</label>
                            <input class="form-control {{ $document["docs_type"] }}_start" type="date"
                                placeholder="mm/dd/yy" name="{{ $document["docs_type"] }}_start"
                                id="{{ $document["docs_type"] }}_start" {{ $is_required }} />
                        </div>
                    @endif
                    @if (isset($document["has_expiry"]) && $document["has_expiry"])
                        <div class="col-md-6 col-12 py-2">
                            <label for="Expiry Date" class="form-label">Expiry
                                Date</label>
                            <input class="form-control {{ $document["docs_type"] }}_expiry" type="date"
                                placeholder="mm/dd/yy" name="{{ $document["docs_type"] }}_expiry"
                                id="{{ $document["docs_type"] }}_expiry" {{ $is_required }} />
                        </div>
                    @endif

                    <div class="d-flex align-items-start align-items-sm-center gap-4 py-2">
                        <div class="button-wrapper w-100">
                            <label for="{{ $document["docs_type"] }}_images" class="w-100 mb-4 me-2" tabindex="0">
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
