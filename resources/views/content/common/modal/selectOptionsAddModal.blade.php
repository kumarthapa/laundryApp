    {{-- Select option add Modal --}}
    <div class="modal fade" id="addSelectOptionsModal" tabindex="-1" aria-modal="true" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title" id="ModalTitle">Add New Option</h4>
                    <button type="button" class="btn btn-label-danger p-2" data-bs-dismiss="modal" aria-label="Close">
                        <i class='bx bx-x'></i>
                    </button>
                </div>

                <form id="commonAddNewSelectOptionForm" method="POST" action="{{ route("settings.save_config") }}"
                    enctype="multipart/form-data">

                    <input type="hidden" id="input_key" name="input_value[value]">
                    <div class="modal-body">
                        {{-- <hr class="my-4 mx-n4"> --}}
                        <div class="row g-2" id="addNewoPtionInput">
                            {{-- <input type="text" class="form-control" placeholder="Enter option"> --}}
                        </div>
                        <div class="alert alert-danger bg-light py-2 ps-0" role="alert" id="already_exists_alert"
                            style="display: none">
                            This option is already exists. Please enter a different option!
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-label-danger" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary option-submit-button">Add</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script>
        $(document).ready(function() {
            $("#already_exists_alert").hide();
            $("#commonAddNewSelectOptionForm").validate({
                submitHandler: function(form) {
                    var submitButton = $(form).find('button[type="submit"]');
                    submitButton.prop('disabled', true).text('Submitting...');

                    // Wrap the check in a Promise
                    checkOptionIsAlreadyExist().then(function(result) {
                        console.log("Result:", result);

                        if (result) {
                            // If the key does not exist, proceed with form submission
                            var formData = new FormData(form);
                            $.ajax({
                                url: $(form).attr("action"),
                                type: $(form).attr('method'),
                                headers: {
                                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr(
                                        'content')
                                },
                                data: formData,
                                contentType: false,
                                processData: false,
                                async: true,
                                success: function(response) {
                                    if (response?.success && response.response) {
                                        console.log(response.response)
                                        $(".option-submit-button").html('Add');
                                        let input_value = (response.response
                                                .input_value) ?
                                            response.response.input_value : '';
                                        let select2_id = $("#select2_id").val();

                                        let current_select2 = $('#' + select2_id);
                                        let newOption = new Option(input_value.name,
                                            input_value.value, false, true);
                                        current_select2.append(newOption).trigger(
                                            'change');
                                        console.log("kkkk", current_select2)
                                        console.log("newOption", newOption)
                                        current_select2.val(input_value.value)
                                            .trigger('change');
                                        toastr.success(response?.message);
                                        $("#addSelectOptionsModal").modal('hide');
                                    } else {
                                        toastr.error(response?.message ??
                                            'Unable to add option!');
                                        $(".option-submit-button").html('Add');
                                    }
                                    $(".option-submit-button").attr('disabled',
                                        false);
                                },
                                error: function(xhr, status, error) {
                                    console.error("Error during submission:",
                                        error);
                                    toastr.error("Error during submission");
                                    submitButton.prop('disabled', false).text(
                                        'Add');
                                }
                            });
                        } else {
                            $("#commonAddNewSelectOptionForm .optionValue").val('')
                            // Key already exists: prevent form submission, keep modal open
                            submitButton.prop('disabled', false).text('Add');
                            return false; // Prevent form submission
                        }
                    }).catch(function(error) {
                        $("#commonAddNewSelectOptionForm .optionValue").val('')
                        console.error("Error during key check:", error);
                        submitButton.prop('disabled', false).text('Add');
                        return false; // Prevent form submission on error
                    });
                }
            });

            $(document).on('change', '#commonAddNewSelectOptionForm #addNewoPtionInput .optionValue', function() {
                let value = $(this).val().trim();
                let key = value.replace(/ /g, "_").toLowerCase();
                $("#commonAddNewSelectOptionForm #input_key").val(key);
                console.log(value);
                checkOptionIsAlreadyExist().then(function(result) {
                    console.log("Result:", result);
                    if (!result) {
                        return false
                    }
                });
            });
        });

        function checkOptionIsAlreadyExist() {
            return new Promise(function(resolve, reject) {
                let input_value = $("#commonAddNewSelectOptionForm .optionValue").val().trim();
                let key = input_value.replace(/ /g, "_").toLowerCase();
                let keyname = $("#setting_key").val() || '';
                if (!keyname) {
                    return resolve(false); // Resolve false if there's no keyname
                }
                $.ajax({
                    url: "{{ route("settings.getconfigValuesByConfigkey") }}",
                    type: 'GET',
                    data: {
                        key_name: keyname
                    },
                    success: function(response) {
                        if (response.success) {
                            let is_exist = response.values.some(function(item) {
                                return item.value === key;
                            });
                            if (is_exist) {
                                $("#addNewoPtionInput .optionValue").addClass('border-danger');
                                $("#already_exists_alert").show();
                                return resolve(false); // Key exists
                            } else {
                                $("#addNewoPtionInput .optionValue").removeClass('border-danger');
                                $("#already_exists_alert").hide();
                                return resolve(true); // Key does not exist
                            }
                        } else {
                            reject("Failed to get config values.");
                        }
                    },
                    error: function(xhr, status, error) {
                        reject(error); // Reject on failure
                    }
                });
            });
        }
    </script>
