@php
    $designation_fields = App\Helpers\UtilityHelper::getConfig('designation_fields');
    $operation_type_fields = App\Helpers\UtilityHelper::getConfig('operation_type_fields');
@endphp
{{-- ------------------------------------------------------ Designation ------------------------------------------------------------- --}}
<div class="card mb-3">
    <h4 class="card-header"><span><i class='bx bxs-user-rectangle'></i></span>Designation Setting</h4>
    <div class="card-body">

        <div class="py-2">
            <p><strong class="fs-4">Designations</strong></p>
        </div>
        <div class="d-flex justify-content-between py-0 my-0 designation_input_div">
            <input type="text" class="form-control" id="designation_fieldname" placeholder="Enter Designation">
            <input type="text" class="form-control bg-label-primary mx-2" id="designation_keyname"
                placeholder="key_name" readonly>
            <button class="btn btn-primary btn-sm addNewFieldsbtn" type="button" id="add_new_designation_movement">
                Add</button>
        </div>
        <form class="needs-validation save_setting_data py-2" novalidate id="save_designation_movement">
            {{ csrf_field() }}
            <input type="hidden" name="submit_form_name" value="save_designation_details">
            <input type="hidden" name="setting_key" value="designation_fields">
            <input type="hidden" name="setting_key_name" value="Add Designation">
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th class="text-nowrap py-2">Filed Name</th>
                            <th class="text-nowrap py-2">Key Name</th>
                            <th class="text-nowrap py-2"></th>
                        </tr>
                    </thead>
                    <tbody class="designation_table_body">
                        <?php
                        $designation = [];
                        if (isset($designation_fields->value) && $designation_fields) {
                            $designation = json_decode($designation_fields->value, true);
                        }
                        ?>
                        @if (isset($designation) && $designation)
                            @foreach ($designation as $key => $values)
                                <tr>
                                    <td class="text-nowrap">
                                        <input type="text" class="form-control" value="{{ $values['name'] }}"
                                            name="designation_details_fields[{{ $key }}][name]">
                                    </td>
                                    <td class="text-nowrap">
                                        <input type="text" class="form-control bg-label-primary designation_keyname"
                                            value="{{ $values['value'] }}"
                                            name="designation_details_fields[{{ $key }}][value]">
                                    </td>
                                    <td class="text-nowrap">
                                        <button class="btn btn-sm btn-danger btn-icon mx-2 mb-1 px-2 delete_row"><i
                                                class="bx bx-trash"></i></button>
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
