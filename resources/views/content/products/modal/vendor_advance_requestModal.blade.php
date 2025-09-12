{{-- Vendor advance update modal --}}
{{-- Vendor advance update modal --}}
<div class="modal fade" id="vendor_advance_modal" tabindex="-1" aria-modal="true" role="dialog" data-bs-focus="false">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="modalCenterTitle"> Update Vendor Advance
                    {{-- <br><small class="text-primary text-uppercase fs-6">
                        Trip ID: {{ $trip->trip_id }} &nbsp; Tour ID: {{ $trip->tour_id }}
                    </small> --}}
                </h4>
                <button type="button" class="btn btn-label-danger p-2" data-bs-dismiss="modal" aria-label="Close">
                    <i class='bx bx-x'></i>
                </button>
            </div>

            <form id="vendor_advance_form" action="{{ route("trips.saveVendorAdvance") }}" method="POST"
                enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12 col-12">
                            <div class="row">
                                <div class="col-md-12 col-12 mb-2">
                                    <h5 class="text-warning">
                                        Vendor Name: {{ $trip->supplier_name ?? "" }} :: Vehicle Number:
                                        {{ $trip->vehicle_number ?? "" }}
                                        ({{ $trip->vehicle_size ?? "" }})
                                    </h5>
                                    <div class="alert alert-warning text-start" role="alert" id="advance_message">
                                        <span>Your Total Income: <strong>Rs.
                                                {{ isset($incomeDetails["item_total"]) ? number_format($incomeDetails["item_total"]) : 0.0 }}</strong></span><br>
                                        <span>Your Receivable Amount: <strong>Rs.
                                                {{ isset($total_received_amount) ? number_format($total_received_amount) : 0.0 }}</strong></span><br>
                                        @if (isset($limit_amount) && $limit_amount > 0)
                                            <span id="default_message">
                                                You are eligible for an amount of <strong>Rs.
                                                    {{ number_format($limit_amount) }}</strong>.<br>
                                                Please enter an amount less than or equal to <strong>Rs.
                                                    {{ number_format($limit_amount) }}</strong>.
                                            </span>
                                        @else
                                            <span>
                                                Sorry, you are not eligible to take an advance. You have already
                                                completed your eligible amount.
                                            </span>
                                        @endif
                                    </div>
                                </div>

                                <div class="col-md-6 col-12 mb-2">
                                    <label class="form-label text-danger" for="required_amount">* Required
                                        Amount</label>
                                    <input type="number" id="required_amount" name="required_amount"
                                        class="form-control" max="{{ $limit_amount ?? 0.0 }}" placeholder="Enter Amount"
                                        required>
                                </div>

                                <div class="col-md-6 col-12 mb-2">
                                    <label class="form-label text-danger" for="required_amount">Reference Trips</label>

                                    <input type="text" class="form-control" name="selected_trip" id="selected_trip"
                                        value="{{ $trip->trip_id ?? "" }}" readonly>

                                </div>

                                <div class="col-md-6 col-12 mb-2">
                                    <?php
                                    $requestPurposeOptions = [
                                        "fuel_advance" => "Fuel Advance",
                                        "cash_advance" => "Cash Advance",
                                        "vehicle_indent" => "Vehicle Indent",
                                    ];
                                    ?>
                                    <label class="form-label text-danger" for="request_purpose">* Request
                                        Purpose</label>
                                    <select id="request_purpose" name="request_purpose" class="form-select" required>
                                        <option value="">Select an option</option>
                                        @foreach ($requestPurposeOptions as $value => $label)
                                            <option value="{{ $value }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>



                                <div class="col-md-6 col-12 mb-2">
                                    <?php
                                    $movementTypeOptions = [
                                        "regular" => "Regular Trips",
                                        "add-hoc" => "Ad-hoc Trips",
                                        "zonal" => "Zonal Trips",
                                    ];
                                    ?>
                                    <label class="form-label text-danger" for="trip_movement_type">Trip Movement
                                        Type</label>
                                    <select id="trip_movement_type" name="trip_movement_type" class="form-select"
                                        required>
                                        @foreach ($movementTypeOptions as $val => $name)
                                            <option value="{{ $val }}">{{ $name }}</option>
                                        @endforeach
                                    </select>
                                    {{-- <input type="text" class="form-control" name="trip_movement_type"
                                        id="trip_movement_type" value="{{ $movement_type ?? "" }}" readonly required> --}}
                                </div>
                                {{-- ---------------------- BPCL Display vehicle_indent_div -------------------------  --}}
                                <div class="col-md-12 col-12 vehicle_indent_div mb-2 mt-2">
                                    <div class="alert alert-danger text-center" role="alert">
                                        <span>
                                            Enable to fetch BPCL CMS Balance details -- access denied!!
                                        </span>
                                    </div>
                                </div>
                                <div class="col-md-6 col-12 vehicle_indent_div mb-2">
                                    <label class="form-label" for="vehicle_number">Vehicle number</label>
                                    <input type="text" class="form-control" name="vehicle_number" id="vehicle_number"
                                        value="{{ $trip->vehicle_number ?? "" }}" readonly>
                                </div>

                                <div class="col-md-6 col-12 vehicle_indent_div mb-2">
                                    <?php
                                    $stationNameOptions = [
                                        "BPCL" => "BPCL", // Bharat Petroleum Corporation Limited
                                        "JIO-BP" => "JIO-BP", // Reliance and bp joint venture
                                        "HPCL" => "HPCL", // Hindustan Petroleum Corporation Limited
                                    ];
                                    ?>
                                    <label class="form-label" for="station_name">Station Name</label>
                                    <select class="form-select" name="station_name" id="station_name">
                                        {{-- <option value="">Select an option</option> --}}
                                        @foreach ($stationNameOptions as $value => $label)
                                            <option value="{{ $value }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                {{-- ---------------------- BPCL Display vehicle_indent_div -------------------------  --}}


                                <div class="col-md-12 col-12 card card-body mb-2">
                                    <div class="row">
                                        <div class="col-md-6 col-12">
                                            <label for="recipients_signature" class="form-label">Recipient’s
                                                signature</label>
                                            <input class="form-control" type="text" id="recipients_signature"
                                                name="recipients_signature" placeholder="Enter Name">
                                        </div>
                                        <div class="col-md-6 col-12">
                                            <label for="Expiry Date" class="form-label">Delivery
                                                Date</label>
                                            <input class="form-control pod_delivery_date flatpickr-input active"
                                                type="text" placeholder="mm/dd/yy" name="delivery_date"
                                                id="pod_delivery_date">
                                        </div>
                                        <div class="col-md-12">
                                            <div class="d-flex align-items-start align-items-sm-center gap-4 py-2">
                                                <div class="button-wrapper w-100">
                                                    <label for="pod_docs_images" class="w-100 mb-4 me-2"
                                                        tabindex="0">
                                                        <span class="d-none d-sm-block">Upload POD Document</span>
                                                        <i class="bx bx-upload d-block d-sm-none"></i>
                                                        <input type="file" id="pod_docs_images"
                                                            name="pod_docs_images[]"
                                                            class="account-file-input form-control">
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>



                                <div class="col-md-12 col-12 mb-2">
                                    <label class="form-label" for="comments">comments</label>
                                    <textarea id="basic-comments" class="form-control" name="comments" placeholder="Write something ..."></textarea>
                                </div>
                            </div>
                            <hr class="mx-n4 mt-4">
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-label-danger" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" id="submit-button" class="btn btn-label-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>



<script>
    function toggleVehicleIndentDiv() {
        const requestPurpose = $("#request_purpose").val();
        if (requestPurpose === "vehicle_indent") {
            $(".vehicle_indent_div").slideDown(200); // 200 ms smooth slide
        } else {
            $(".vehicle_indent_div").slideUp(200); // 200 ms smooth slide
        }
    }

    // Call once when page loads
    toggleVehicleIndentDiv();
    $(document).ready(function() {

        // For Regular Advance, allow up to 40% of total income
        let type = $("#trip_movement_type").val() || '';
        if (type === "regular") {
            const total_received_amount = {{ isset($total_received_amount) ? $total_received_amount : 0.0 }};
            const income = {{ isset($incomeDetails["item_total"]) ? $incomeDetails["item_total"] : 0.0 }};
            const eligibleAmount = {{ $limit_amount ?? 0 }};
            const percentageIncome = Math.round(income * 0.4); // 40%

            // Limit to the lower of eligibleAmount and 40% of income
            let maxAmount = Math.min(percentageIncome, eligibleAmount);

            // Subtract already received amount
            maxAmount = Math.max(maxAmount - total_received_amount, 0); // Prevent negative value

            $("#default_message").html(
                `You have selected <b>Regular Trips</b>. You can request up to <strong>Rs. ${maxAmount.toLocaleString()}</strong>. (40% Of Total Income minus received)`
            );

            $("#required_amount").attr("max", maxAmount);
        }



        $(".vehicle_indent_div").hide();
        // Also trigger on change
        $("#request_purpose").on("change", function() {
            toggleVehicleIndentDiv();
        });

        flatpickr("#pod_delivery_date", {
            monthSelectorType: "static",
            enableTime: true
        });

        let selectInputs = [
            'request_purpose',
            'station_name',
            'frequency'
        ];
        selectInputs.forEach(id => {
            $(`#${id}`).select2({
                dropdownParent: $('#vendor_advance_modal'), // <-- Important inside modal
                placeholder: "Select an option",
                allowClear: true,
                minimumInputLength: 0
            });
        });

        // Form validation
        $("#vendor_advance_form").validate({
            rules: {
                required_amount: {
                    required: true
                }
            },
            messages: {
                required_amount: {
                    required: 'Please enter the amount.'
                }
            },
            errorPlacement: function(error, element) {
                if (element.parent('.input-group').length) {
                    error.insertAfter(element.parent());
                } else {
                    error.insertAfter(element);
                }
            },
            submitHandler: function(form) {
                $("#submit-button").attr('disabled', true).html('Saving...');
                let formData = new FormData(form);
                console.log("Submitting vendor advance");

                $.ajax({
                    url: $(form).attr('action'),
                    type: $(form).attr('method'),
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    data: formData,
                    contentType: false,
                    processData: false,
                    success: function(response) {
                        if (response?.success) {
                            $("#submit-button").html('Saved');
                            toastr.success(response?.message);
                            setTimeout(() => {
                                window.location.href = response?.return_url;
                            }, 1500);
                        } else {
                            toastr.error(response?.message ?? 'Unable to save data!');
                            $("#submit-button").html('Save');
                        }
                        $("#submit-button").attr('disabled', false);
                    },
                    error: function(xhr, status, error) {
                        console.error("Error:", error);
                        toastr.error(error);
                        $("#submit-button").attr('disabled', false).html('Save');
                    }
                });
            }
        });

        const totalReceivedAmount = {{ isset($total_received_amount) ? $total_received_amount : 0.0 }};
        const totalIncome = {{ isset($incomeDetails["item_total"]) ? $incomeDetails["item_total"] : 0.0 }};
        const eligibleAmount = {{ $limit_amount ?? 0 }};
        const fortyPercentIncome = Math.round(totalIncome * 0.4);
        const eightyPercentIncome = Math.round(totalIncome * 0.8);
        const ninetyPercentIncome = Math.round(totalIncome * 0.9);

        $("#trip_movement_type").on("change", function() {
            const movementType = $(this).val();
            let rawLimit = eligibleAmount;

            if (movementType === "add-hoc") {
                rawLimit = eightyPercentIncome;
                $("#default_message").html(
                    `You have selected <b>Add-hoc Trips</b>. You can request up to <strong>Rs. ${rawLimit.toLocaleString()}</strong>. (80% Of Total Income)`
                );
            } else if (movementType === "zonal") {
                rawLimit = ninetyPercentIncome;
                $("#default_message").html(
                    `You have selected <b>Zonal Trips</b>. You can request up to <strong>Rs. ${rawLimit.toLocaleString()}</strong>. (90% Of Total Income)`
                );
            } else {
                rawLimit = fortyPercentIncome;
                $("#default_message").html(
                    `You have selected <b>Regular Trips</b>. You can request up to <strong>Rs. ${rawLimit.toLocaleString()}</strong>. (40% Of Total Income)`
                );
            }

            // Ensure the max amount doesn't exceed business limit and subtract already received
            let maxAmount = Math.min(rawLimit, eligibleAmount) - totalReceivedAmount;
            maxAmount = Math.max(maxAmount, 0); // Avoid negative

            $("#required_amount").val('');
            $("#required_amount").attr("max", maxAmount);
        });

        $("#required_amount").on("input", function() {
            const enteredAmount = parseFloat($(this).val()) || 0;
            const maxAllowed = parseFloat($(this).attr("max")) || 0;

            if (enteredAmount > maxAllowed) {
                alert(
                    `Entered amount exceeds the allowed limit of Rs. ${maxAllowed.toLocaleString()}.`
                );
                $(this).val(maxAllowed);
            }
        });



    });
</script>
