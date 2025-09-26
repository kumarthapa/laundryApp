{{-- ------------------------------------------------------ Product Setting ------------------------------------------------------------- --}}
<div class="card mb-3">
    <div class="card-header">
        <div class="d-flex justify-content-between my-0 py-0">
            <h4 class="card-title"><span><i class='bx bx-spreadsheet'></i></span>Product Setting</h4>
        </div>
    </div>
    <div class="card-body">
        <div class="py-2">
            <p><strong class="fs-5">Product Stages</strong></p>
        </div>
        <div class="d-flex justify-content-between my-0 py-0">
            <input type="text" class="form-control me-3" id="product_stage_labelname" placeholder="Enter Stage Name">
            <button class="btn btn-primary btn-sm" type="button" id="add_new_stage_label">
                <i class="bx bx-plus me-1"></i>Add</button>
        </div>
        <form class="needs-validation save_setting_data py-2" novalidate id="save_product_stage_form">
            {{ csrf_field() }}
            <input type="hidden" name="submit_form_name" value="save_product_process_stages">
            <input type="hidden" name="setting_key" value="product_process_stages">
            <input type="hidden" name="setting_key_name" value="Product Process Stages">
            <div class="table-responsive">
                <table class="mt-2 table">
                    <thead>
                        <tr>
                            <th class="text-nowrap py-2">Label Name</th>
                            <th class="text-nowrap py-2"></th>
                        </tr>
                    </thead>
                    <tbody class="product_stage_table_body">
                        @if (!empty($product_process_stages))
                            @foreach ($product_process_stages as $key => $values)
                                <tr>
                                    <td class="text-nowrap">
                                        <input type="text" class="form-control" value="{{ $values["name"] }}"
                                            name="product_process_stages[{{ $key }}][name]">
                                        <input type="hidden" class="product_stage" value="{{ $values["value"] }}"
                                            name="product_process_stages[{{ $key }}][value]">
                                    </td>
                                    <td class="text-nowrap">
                                        <button class="btn btn-sm btn-danger btn-icon delete_row mx-2 mb-1 px-2">
                                            <i class="bx bx-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        @endif
                    </tbody>
                </table>
            </div>
            <div class="py-2 text-end">
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>

{{-- ------------------------------------------------------ Product Status ------------------------------------------------------------- --}}
<div class="card mb-3">
    <h4 class="card-header"><span><i class='bx bx-spreadsheet'></i></span>Product Status</h4>
    <div class="card-body">
        <div class="py-2">
            <p><strong class="fs-5">Product Status</strong></p>
        </div>
        <div class="d-flex justify-content-between my-0 py-0">
            <input type="text" class="form-control me-3" id="product_status_labelname"
                placeholder="Enter Status Name">
            <button class="btn btn-primary btn-sm" type="button" id="add_new_status_label">
                <i class="bx bx-plus me-1"></i>Add</button>
        </div>
        <form class="needs-validation save_setting_data py-2" novalidate id="save_product_status_form">
            {{ csrf_field() }}
            <input type="hidden" name="submit_form_name" value="save_product_status">
            <input type="hidden" name="setting_key" value="product_status">
            <input type="hidden" name="setting_key_name" value="Product Status">
            <div class="table-responsive">
                <table class="mt-2 table">
                    <thead>
                        <tr>
                            <th class="text-nowrap py-2">Label Name</th>
                            <th class="text-nowrap py-2"></th>
                        </tr>
                    </thead>
                    <tbody class="product_status_table_body">
                        @if (!empty($product_status))
                            @foreach ($product_status as $key => $values)
                                <tr>
                                    <td class="text-nowrap">
                                        <input type="text" class="form-control" value="{{ $values["name"] }}"
                                            name="product_status[{{ $key }}][name]">
                                        <input type="hidden" class="product_status" value="{{ $values["value"] }}"
                                            name="product_status[{{ $key }}][value]">
                                    </td>
                                    <td class="text-nowrap">
                                        <button class="btn btn-sm btn-danger btn-icon delete_row mx-2 mb-1 px-2">
                                            <i class="bx bx-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        @endif
                    </tbody>
                </table>
            </div>
            <div class="py-2 text-end">
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>

{{-- ------------------------------------------------------ Product Defect Points ------------------------------------------------------------- --}}


<div class="card mb-3">
    <h4 class="card-header"><span><i class='bx bx-spreadsheet'></i></span>Product Defect Points</h4>
    <div class="card-body">
        @if (!empty($product_process_stages))
            <form class="needs-validation save_setting_data py-2" novalidate id="save_product_defect_points_form">
                {{ csrf_field() }}
                <input type="hidden" name="submit_form_name" value="save_product_defect_points">
                <input type="hidden" name="setting_key" value="product_defect_points">
                <input type="hidden" name="setting_key_name" value="Product Defect Points">
                @foreach ($product_process_stages as $stageKey => $stage)
                    @php
                        $stage_name = $stage["name"];
                        $stage_key = $stage["value"];
                    @endphp

                    <div class="stage-block py-2" data-stage="{{ $stage_key }}">
                        <p><strong class="fs-5">{{ $stage_name }}</strong></p>

                        <div class="d-flex justify-content-between my-0 py-0">
                            <input type="text" class="form-control product_defect_points_labelname me-3"
                                placeholder="Enter Defect Point">
                            <button class="btn btn-primary btn-sm add_new_defect_label" type="button">
                                <i class="bx bx-plus me-1"></i>Add
                            </button>
                        </div>

                        <div class="table-responsive">
                            <table class="mt-2 table">
                                <thead>
                                    <tr>
                                        <th class="text-nowrap py-2">Label Name</th>
                                        <th class="text-nowrap py-2"></th>
                                    </tr>
                                </thead>
                                <tbody class="product_defect_points_table_body">
                                    @if (!empty($product_defect_points[$stage_key]))
                                        @foreach ($product_defect_points[$stage_key] as $pointIndex => $pointValue)
                                            {{-- {{ json_encode($pointIndex) }} --}}
                                            <tr>
                                                <td class="text-nowrap">
                                                    <input type="text" class="form-control"
                                                        value="{{ $pointValue["name"] }}"
                                                        name="product_defect_points[{{ $stage_key }}][{{ $pointIndex }}][name]">

                                                    <input type="hidden" class="form-control"
                                                        value="{{ $pointValue["value"] }}"
                                                        name="product_defect_points[{{ $stage_key }}][{{ $pointIndex }}][value]">
                                                </td>
                                                <td class="text-nowrap">
                                                    <button type="button"
                                                        class="btn btn-sm btn-danger btn-icon delete_row mx-2 mb-1 px-2">
                                                        <i class="bx bx-trash"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        @endforeach
                                    @endif
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endforeach
                <div class="py-2 text-end">
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        @endif
    </div>
</div>
