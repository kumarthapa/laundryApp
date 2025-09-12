{{-- ------------------------------------------------------ Product Setting ------------------------------------------------------------- --}}
<div class="card mb-3">
    <h4 class="card-header"><span><i class='bx bx-spreadsheet'></i></span>Product Setting</h4>
    <div class="card-body">
        <div class="py-2">
            <p><strong class="fs-4">Product Process Stages</strong></p>
        </div>
        <div class="d-flex justify-content-between py-0 my-0">
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
                <table>
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
                                        <input type="text" class="form-control" value="{{ $values['name'] }}"
                                            name="product_process_stages[{{ $key }}][name]">
                                        <input type="hidden" class="product_stage" value="{{ $values['value'] }}"
                                            name="product_process_stages[{{ $key }}][value]">
                                    </td>
                                    <td class="text-nowrap">
                                        <button class="btn btn-sm btn-danger btn-icon mx-2 mb-1 px-2 delete_row">
                                            <i class="bx bx-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        @endif
                    </tbody>
                </table>
            </div>
            <div class="text-end py-2">
                <button type="submit" class="btn btn-primary">Save</button>
            </div>
        </form>
    </div>
</div>

{{-- ------------------------------------------------------ Product Status ------------------------------------------------------------- --}}
<div class="card mb-3">
    <h4 class="card-header"><span><i class='bx bx-spreadsheet'></i></span>Product Status</h4>
    <div class="card-body">
        <div class="py-2">
            <p><strong class="fs-4">Product Status</strong></p>
        </div>
        <div class="d-flex justify-content-between py-0 my-0">
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
                <table>
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
                                        <input type="text" class="form-control" value="{{ $values['name'] }}"
                                            name="product_status[{{ $key }}][name]">
                                        <input type="hidden" class="product_status" value="{{ $values['value'] }}"
                                            name="product_status[{{ $key }}][value]">
                                    </td>
                                    <td class="text-nowrap">
                                        <button class="btn btn-sm btn-danger btn-icon mx-2 mb-1 px-2 delete_row">
                                            <i class="bx bx-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        @endif
                    </tbody>
                </table>
            </div>
            <div class="text-end py-2">
                <button type="submit" class="btn btn-primary">Save</button>
            </div>
        </form>
    </div>
</div>
