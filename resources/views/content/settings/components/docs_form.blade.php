{{-- ------------------------------------ Save Documents types card component ------------ START ----------------------- --}}

<div class="col-md-6 col-12">
    <div class="card save_docstyps_card mb-2" style="height: 500px">
        <div class="card-header py-3">
            <p class="d-none"> config_key : {{ $config_key }}</p>
            <h4 class="card-title"><span><i class='bx bxs-file-doc'></i></span> {{ $form_title }}</h4>
        </div>
        <div class="card-body pb-1">
            <div class="row bg-label-secondary rounded-1 p-2">
                <div class="col-md-6 col-12 p-0">
                    <label for="Docs Name" class="ps-1">Docs Types</label>
                    @if (isset($docs) && $docs)
                        <select class="form-select select_docs_type">
                            <option value="">Select Docs Type</option>
                            @foreach (json_decode($docs, true) as $key => $data)
                                <option value="{{ $data["docs_type"] }}">{{ $data["name"] }}</option>
                            @endforeach
                        </select>
                    @endif
                </div>
                <div class="col-md-3 col-12 text-center p-0">
                    <label for="Is Required">Is Required</label>
                    <div class="form-switch">
                        <label class="switch">
                            <input class="form-check-input fs-5 is_required" type="checkbox" value="1" />
                        </label>
                    </div>
                </div>
                <div class="col-md-3 col-12 text-center p-0">
                    <a href="javascript:;" class="btn btn-primary btn-sm add_modules_docs_types mt-3">
                        <i class="bx bx-plus bx-xs me-1"></i>Add New</a>
                </div>
            </div>

            <div class="row py-2">
                <div class="col-md-12 p-0">
                    <form class="needs-validation save_setting_data py-2">
                        {{ csrf_field() }}
                        <input type="hidden" name="submit_form_name" value="save_documents_types">
                        <input type="hidden" name="config_key" value="{{ $config_key }}">
                        <input type="hidden" name="config_name" value="{{ $config_name }}">
                        <div class="table-responsive" style="height:250px; overflow-y: auto;">
                            <table class="table table-borderless">
                                <thead>
                                    <tr>
                                        <th class="text-nowrap py-2">Document Name</th>
                                        <th class="text-nowrap py-2">Is Required</th>
                                        <th class="text-nowrap py-2">Delete</th>
                                    </tr>
                                </thead>
                                <tbody class="docs_types_list_body">
                                    @if (isset($docs_types) && $docs_types)
                                        @foreach (json_decode($docs_types, true) as $key => $value)
                                            <tr>
                                                <td class="text-nowrap py-1">
                                                    <input type="text" class="form-control docs_type"
                                                        value="{{ $value["docs_type"] }}"
                                                        name="docs[{{ $key }}][docs_type]" readonly>
                                                </td>
                                                <td class="text-nowrap py-1">
                                                    <div class="form-switch">
                                                        <label class="switch mx-4">
                                                            <input class="form-check-input fs-5" type="checkbox"
                                                                value="1"
                                                                name="docs[{{ $key }}][is_required]"
                                                                @if ($value["is_required"]) checked @endif>
                                                        </label>
                                                    </div>
                                                </td>
                                                <td class="text-nowrap py-1">
                                                    <button
                                                        class="btn btn-sm btn-danger btn-icon mb-1 remove_docs_types"><i
                                                            class="bx bx-trash"></i></button>
                                                </td>
                                            </tr>
                                        @endforeach
                                    @endif
                                </tbody>
                            </table>
                        </div>
                        <hr>
                        <div class="text-end">
                            <button type="submit" class="btn btn-primary me-1">Save</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
{{-- ------------------------------------ Save Documents types card component ------------ END ----------------------- --}}
