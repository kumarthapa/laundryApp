    {{-- Update trip modal --}}
    @php
        $exchange_reasons = App\Helpers\ConfigHelper::getExchangeReasons();
    @endphp
    <div class="modal fade" id="update_trip_modal" tabindex="-1" aria-modal="true" role="dialog" data-bs-focus="false">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title" id="modalCenterTitle"> Update trip movement timing
                        <br><small class="text-primary text-uppercase fs-6">
                            Trip id: {{ $trip->trip_id }} &nbsp; Tour id: {{ $trip->tour_id }}
                        </small>
                    </h4>
                    <button type="button" class="btn btn-label-danger p-2" data-bs-dismiss="modal" aria-label="Close">
                        <i class='bx bx-x'></i>
                    </button>
                </div>
                <form id="trip_update_form" action="{{ route("trips.updateTrip") }}" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="trip_id" value="{{ $trip->trip_id }}">
                        <input type="hidden" name="tour_id" value="{{ $trip->tour_id }}">
                        {{-- @php
                            echo json_encode($trip);
                        @endphp --}}

                        <div class="row my-4 mb-6">
                            <div class="col-sm-6 col-md-3 col-12">
                                <label for="toll_fee" class="form-label">
                                    Toll Fee</label>
                                <input type="number" class="form-control" placeholder="Enter toll fee" id="toll_fee"
                                    name="toll_fee" value="{{ isset($trip->toll_fee) ? $trip->toll_fee : "" }}">
                            </div>
                            <div class="col-sm-6 col-md-3 col-12">
                                <label for="parking_fee" class="form-label">
                                    Parking Fee</label>
                                <input type="number" class="form-control" placeholder="Enter parking fee"
                                    id="parking_fee" name="parking_fee"
                                    value="{{ isset($trip->parking_fee) ? $trip->parking_fee : "" }}">
                            </div>


                            <div class="col-sm-6 col-md-3 col-12">
                                <label for="extra_kms" class="form-label">
                                    Extra Kms</label>
                                <input type="number" class="form-control" placeholder="Enter extra kms" id="extra_kms"
                                    name="extra_kms"
                                    @if (isset($trip->extra_kms) && $trip->extra_kms) value="{{ $trip->extra_kms }}" @endif>
                            </div>
                            <div class="col-sm-6 col-md-3 col-12">
                                <label for="extra_time" class="form-label">
                                    Extra Time</label>
                                <input type="datetime" class="form-control flatpickr-input time_picker"
                                    placeholder="HH:MM:SS" id="extra_time"
                                    @if (isset($trip->extra_time) && $trip->extra_time) value="{{ $trip->extra_time }}" @endif
                                    name="extra_time">
                            </div>

                        </div>
                        <div class="row my-4 mb-6">
                            <div class="col-sm-6 col-md-3 col-12">
                                <label for="opening_kms" class="form-label">
                                    Opening Kms</label>
                                <input type="number" class="form-control" placeholder="Enter Opening Kms"
                                    id="opening_kms" name="opening_kms"
                                    @if (isset($trip->opening_kms) && $trip->opening_kms) value="{{ $trip->opening_kms }}" @endif>
                            </div>
                            <div class="col-sm-6 col-md-3 col-12">
                                <label for="closing_kms" class="form-label">
                                    Closing Kms</label>
                                <input type="number" class="form-control" placeholder="Enter Closing Kms<"
                                    id="closing_kms" name="closing_kms"
                                    @if (isset($trip->closing_kms) && $trip->closing_kms) value="{{ $trip->closing_kms }}" @endif>
                            </div>


                            <div class="col-sm-6 col-md-3 col-12">
                                <label for="total_kms" class="form-label">
                                    Total Kms</label>
                                <input type="number" class="form-control" placeholder="Enter Total Kms" id="total_kms"
                                    name="total_kms"
                                    @if (isset($trip->total_kms) && $trip->total_kms) value="{{ $trip->total_kms }}" @endif>
                            </div>
                            <div class="col-sm-6 col-md-3 col-12">
                                <label for="total_time" class="form-label">
                                    Total Time </label>
                                <input type="datetime" class="form-control flatpickr-input time_picker"
                                    placeholder="HH:MM:SS" id="total_time"
                                    @if (isset($trip->total_time) && $trip->total_time) value="{{ $trip->total_time }}" @endif
                                    name="total_time">
                            </div>
                            {{-- ----------------- Not using Trip Visible Time -------------------- --}}
                            <div class="col-sm-6 col-md-3 col-12 d-none">
                                <label for="total_time" class="form-label">
                                    Trip Visible Time </label>
                                <input type="datetime" class="form-control flatpickr-input time_picker" disabled
                                    placeholder="HH:MM:SS" id="updateTripVisibleTime"
                                    @if (isset($trip->trip_visible_time) && $trip->trip_visible_time) value="{{ $trip->trip_visible_time }}" @endif
                                    name="updateTripVisibleTime">
                            </div>
                            {{-- ----------------- Not using Trip Visible Time -------------------- --}}
                        </div>

                        <hr class="mx-n4 mt-4">
                        <h5 class=""><i class='bx bxs-map-pin'></i> Trip Locations</h5>
                        <div class="row my-4 mb-6">
                            <div class="col-md-12">
                                <ul class="timeline mb-0">
                                    {{-- <li>
                                        <span class="text-primary" for="placement_location">Placement
                                            Location
                                        </span>
                                    </li> --}}
                                    {{-- ---------------------------------------------------- trip stating point start -------------------------------------------------- --}}
                                    <li class="timeline-item border-left-dashed ps-4">

                                        <div class="row my-2">
                                            <div class="col-md-7">
                                                <span
                                                    class="timeline-indicator-advanced timeline-indicator-success border-0 shadow-none">
                                                    <i class="bx bx-check-circle"></i>
                                                </span>
                                                <div class="timeline-event ps-1">
                                                    <div class="timeline-header">
                                                        <small class="text-success text-uppercase">
                                                            {{ $trip->starting_point }}</small>
                                                    </div>
                                                    @if (isset($trip->starting_point_location->full_address))
                                                        <p class="text-body mb-0">
                                                            {{ $trip->starting_point_location->location_name }} :
                                                            {{ $trip->starting_point_location->full_address }}
                                                        </p>
                                                    @endif
                                                    <div class="d-flex">
                                                        <span class="badge bg-label-primary d-flex me-2">
                                                            <p class="m-0 me-2">Arrival:</p>
                                                            {{ $trip->sp_arrival_time }}
                                                        </span>
                                                        <span class="badge bg-label-primary d-flex">
                                                            <p class="m-0 me-2">Departure:</p>
                                                            {{ $trip->sp_departure_time }}
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-5">
                                                <div class="d-flex">
                                                    <div class="mx-1">
                                                        <label for="Placement Time" class="form-label">
                                                            SP Arrived Time</label>
                                                        <input type="datetime"
                                                            class="form-control flatpickr-input time_picker"
                                                            placeholder="HH:MM:SS" id="sp_arrived_time"
                                                            @if (isset($trip->sp_arrived_time)) value="{{ $trip->sp_arrived_time }}" @endif
                                                            name="sp_arrived_time">
                                                    </div>
                                                    <div class="mx-1">
                                                        <label for="Departure Time" class="form-label">
                                                            SP Departure Time</label>
                                                        <input type="datetime"
                                                            class="form-control flatpickr-input time_picker"
                                                            placeholder="HH:MM:SS" id="sp_departured_time"
                                                            name="sp_departured_time"
                                                            @if (isset($trip->sp_departured_time)) value="{{ $trip->sp_departured_time }}" @endif>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </li>


                                    {{-- touchpoints timing ---------------------------------------------------- start ----------------- --}}
                                    @if (isset($trip->trip_touchpoints))
                                        @foreach ($trip->trip_touchpoints as $touch_point)
                                            <li class="timeline-item border-left-dashed ps-4">
                                                {{-- @php echo print_r($touch_point) @endphp --}}
                                                <div class="row my-2">
                                                    <div class="col-md-7">
                                                        <span
                                                            class="timeline-indicator-advanced timeline-indicator-warning border-0 shadow-none">
                                                            <i class="bx bx-check-circle"></i>
                                                        </span>
                                                        <div class="timeline-event ps-1">
                                                            <div class="timeline-header">
                                                                <small class="text-warning text-uppercase">
                                                                    {{ $touch_point->touch_point }} : TP number
                                                                    {{ $touch_point->tp_number }}</small>
                                                            </div>
                                                            @if (isset($touch_point->location->full_address))
                                                                <p class="text-body mb-0">
                                                                    {{ $touch_point->location_name }} :
                                                                    {{ $touch_point->location->full_address }}
                                                                </p>
                                                            @endif
                                                            <div class="d-flex">
                                                                <span class="badge bg-label-primary d-flex me-2">
                                                                    <p class="m-0 me-2">Arrival:</p>
                                                                    {{ $touch_point->arrival_time }}
                                                                </span>
                                                                <span class="badge bg-label-primary d-flex">
                                                                    <p class="m-0 me-2">Departure:</p>
                                                                    {{ $touch_point->departure_time }}
                                                                </span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-5">
                                                        <div class="d-flex">
                                                            <div class="mx-1">
                                                                <label for="Placement Time" class="form-label">
                                                                    Arrived Time
                                                                </label>
                                                                <input type="datetime"
                                                                    class="form-control flatpickr-input time_picker"
                                                                    placeholder="HH:MM:SS" value=""
                                                                    id="arrived_time_{{ $touch_point->tp_number }}"
                                                                    value="{{ isset($touch_point->arrived_time) ? $touch_point->arrived_time : "" }}"
                                                                    name="touchpoint_timing[{{ $touch_point->tp_number }}][arrived_time]">
                                                            </div>
                                                            <div class="mx-1">
                                                                <label for="Departure Time" class="form-label">
                                                                    Departured Time</label>
                                                                <input type="datetime"
                                                                    class="form-control flatpickr-input time_picker"
                                                                    placeholder="HH:MM:SS"
                                                                    id="departured_time_{{ $touch_point->tp_number }}"
                                                                    name="touchpoint_timing[{{ $touch_point->tp_number }}][departured_time]"
                                                                    value="{{ isset($touch_point->departured_time) ? $touch_point->departured_time : "" }}">
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </li>
                                        @endforeach
                                    @endif

                                    {{-- touchpoints timing ------------------------------------------------ end ------------------------ --}}


                                    {{-- ---------------------------------------------------- trip stating point end -------------------------------------------------- --}}
                                    {{-- <li>
                                        <span class="text-primary" for="placement_location">Destination
                                            Location
                                        </span>
                                    </li> --}}
                                    {{-- ---------------------------------------------------- trip destination start -------------------------------------------------- --}}
                                    <li class="timeline-item border-left-dashed border-transparent ps-4">
                                        <div class="row my-2">
                                            <div class="col-md-7">
                                                <span
                                                    class="timeline-indicator-advanced timeline-indicator-danger border-0 shadow-none">
                                                    <i class="bx bx-map"></i>
                                                </span>
                                                <div class="timeline-event ps-1">
                                                    <div class="timeline-header">
                                                        <small class="text-danger text-uppercase">
                                                            {{ $trip->destination_point }} </small>
                                                    </div>
                                                    @if (isset($trip->destination_location->full_address))
                                                        <p class="text-body mb-0">
                                                            {{ $trip->destination_location->location_name }} :
                                                            {{ $trip->destination_location->full_address }}
                                                        </p>
                                                    @endif
                                                    <div class="d-flex">
                                                        <span class="badge bg-label-primary d-flex me-2">
                                                            <p class="m-0 me-2">Arrival:</p>
                                                            {{ $trip->dp_arrival_time }}
                                                        </span>
                                                        <span class="badge bg-label-primary d-flex">
                                                            <p class="m-0 me-2">Departure:</p>
                                                            {{ $trip->sp_departure_time }}
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-5">
                                                <div class="d-flex">
                                                    <div class="mx-1">
                                                        <label for="Arrival Time" class="form-label">
                                                            DP Arrival Time</label>
                                                        <input type="datetime"
                                                            class="form-control flatpickr-input time_picker"
                                                            placeholder="HH:MM:SS" id="dp_arrived_time"
                                                            @if (isset($trip->dp_arrived_time)) value="{{ $trip->dp_arrived_time }}" @endif
                                                            name="dp_arrived_time">
                                                    </div>
                                                    <div class="mx-1">
                                                        <label for="Departure Time" class="form-label">
                                                            DP Departure Time</label>
                                                        <input type="datetime"
                                                            class="form-control flatpickr-input time_picker"
                                                            placeholder="HH:MM:SS" id="dp_departured_time"
                                                            @if (isset($trip->dp_departured_time)) value="{{ $trip->dp_departured_time }}" @endif
                                                            name="dp_departured_time">
                                                    </div>
                                                </div>
                                            </div>
                                    </li>
                                    {{-- ---------------------------------------------------- trip destination end -------------------------------------------------- --}}
                                </ul>
                            </div>
                        </div>

                        <div class="d-flex align-items-start align-items-center">
                            <h4>Vehicle Exchange: </h4>
                            <div class="form-switch mb-3 ms-4">
                                <label class="switch">
                                    <input class="form-check-input fs-4" type="checkbox" value="1"
                                        id="vehicle_exchange_switch_enable" name="vehicle_exchange_switch_enable"
                                        readonly>
                                </label>
                            </div>
                        </div>
                        <div class="card border p-3 shadow-sm" id="vehicle_exchange_update_div"
                            style="display: none;">
                            <div class="card-header bg-light">
                                <h5 class="mb-0">Vehicle Exchange</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <!-- Vehicle Selection -->
                                    <div class="col-md-6">
                                        <label class="form-label">Select Vehicle</label>
                                        <select id="vehicle_exchange_select" name="exchange_vehicle"
                                            class="select2 form-select">
                                            <option value="">Select a vehicle</option>
                                        </select>
                                    </div>

                                    <!-- Driver Selection -->
                                    <div class="col-md-6">
                                        <label class="form-label">Select Driver</label>
                                        <select id="driver_select_exchange" name="exchange_driver"
                                            class="select2 form-select">
                                            <option value="">Select a driver</option>
                                        </select>
                                    </div>

                                    <!-- Reason for Change -->
                                    <div class="col-md-12 mt-4">
                                        <label class="form-label">Reason for Change</label>
                                        <select id="delay_reason" name="exchange_reason" class="select2 form-select">
                                            <option value="">Select a reason</option>
                                            @foreach ($exchange_reasons as $key => $value)
                                                <option value="{{ $key }}">{{ $value }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    {{-- Comment --}}
                                    <div class="col-md-12 mt-4">
                                        <label class="form-label">Exchange Comment</label>
                                        <textarea id="basic-comments" class="form-control" name="exchange_comment" placeholder="Write something ...">{{ isset(json_decode($trip->vehicle_exchange_details)->exchange_comment) ? json_decode($trip->vehicle_exchange_details)->exchange_comment : "" }}</textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row my-4 mb-6">
                            {{-- <div class="col-md-12 col-12">
                                <h5 class="mb-2"><i class="bx bxs-file-doc"></i> Upload POD Document</h5>
                            </div> --}}
                            {{-- ------------------- Include POD documents input files ------------------- START ------------ --}}
                            @include("content.common.form-files", [
                                "docs" => $trips_documents,
                                "title" => "Upload POD Document",
                            ])
                            {{-- ------------------- Include POD documents input files ------------------- END ------------ --}}

                        </div>


                        <div class="row my-4 mb-6">
                            <div class="col-md-12">
                                <div class="mb-6">
                                    <label class="form-label" for="basic-comments">Message</label>
                                    <textarea id="basic-comments" class="form-control" name="comments" placeholder="Write something ...">{{ $trip->comments }}</textarea>
                                </div>
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
        $(document).ready(function() {
            flatpickr(".time_picker", {
                enableTime: true,
                noCalendar: true,
                dateFormat: "H:i:s",
            })
            let vehicle_exchange_switch_enable = false;
            $(document).on('change', '#vehicle_exchange_switch_enable', function() {
                $('#vehicle_exchange_update_div').toggle(this.checked);
                console.log('this.checked ---> ', this.checked);
                vehicle_exchange_switch_enable = this.checked;
            });

            let vehicleExchangeDetails = JSON.parse(@json($trip["vehicle_exchange_details"]));

            // Custom template for displaying vehicle details
            function formatVehicle(vehicle) {
                if (!vehicle.id) return vehicle.text; // Placeholder text

                return $(`
                  <div style="display: flex; flex-direction: column;">
                    <strong style="font-size: 14px;">${vehicle.text.split(" - ")[0]}</strong>
                    <span style="font-size: 12px; color: gray;">${vehicle.text.split(" - ")[1]}</span>
                  </div>
                `);
            }

            let $vehicle_exchange_select = $("#vehicle_exchange_select");
            let $driver_select_exchange = $("#driver_select_exchange");

            // Populate dropdown
            @json($vehicles).forEach(vehicle => {
                let displayText =
                    `${vehicle.vehicle_name} - ${vehicle.vehicle_number} - ${vehicle.status??''}`;
                let option = new Option(displayText, vehicle.vehicle_number, false, false);
                $vehicle_exchange_select.append(option);
            });

            // Populate dropdown with driver_code as value
            @json($drivers).forEach(driver => {
                let displayText = `${driver.name} - ${driver.mobile_number}`;
                let option = new Option(displayText, driver.driver_code, false,
                    false); // Set driver_code as value
                $driver_select_exchange.append(option);
            });

            // Initialize Select2 with custom display
            $vehicle_exchange_select.select2({
                allowClear: true,
                placeholder: "Select a vehicle",
                templateResult: formatVehicle,
            }).on("select2:open", function() {
                $(".select2-dropdown").css("z-index", "9999");
            });

            $driver_select_exchange.select2({
                allowClear: true,
                placeholder: "Select a driver",
                templateResult: formatVehicle,
            }).on("select2:open", function() {
                $(".select2-dropdown").css("z-index", "9999");
            });

            $("#delay_reason").select2({
                allowClear: true,
                placeholder: "Select a reason"
            }).on("select2:open", function() {
                $(".select2-dropdown").css("z-index", "9999");
            });

            // **Preselect Values if Available**
            if (vehicleExchangeDetails) {
                if (vehicleExchangeDetails?.exchange_vehicle) {
                    $vehicle_exchange_select.val(vehicleExchangeDetails?.exchange_vehicle).trigger("change");
                }
                if (vehicleExchangeDetails?.exchange_driver) {
                    $driver_select_exchange.val(vehicleExchangeDetails?.exchange_driver).trigger("change");
                }
                if (vehicleExchangeDetails?.exchange_reason) {
                    $('#delay_reason').val(vehicleExchangeDetails?.exchange_reason).trigger("change");
                }
            }

            $("#vehicle_size").select2({
                autoClear: true,
                dropdownParent: $("#update_trip_modal"),
            });
            $("#trip_update_form").validate({
                submitHandler: function(form) {
                    $("#submit-button").attr('disabled', true);
                    $("#submit-button").html('Saving...');
                    var formData = new FormData(form);

                    formData.append('vehicle_exchange_switch_enable', vehicle_exchange_switch_enable);

                    function toRadians(degrees) {
                        return degrees * (Math.PI / 180);
                    }

                    function haversineDistance(lat1, lon1, lat2, lon2) {
                        const R = 6371; // Radius of the Earth in km
                        const dLat = toRadians(lat2 - lat1);
                        const dLon = toRadians(lon2 - lon1);

                        const a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
                            Math.cos(toRadians(lat1)) * Math.cos(toRadians(lat2)) *
                            Math.sin(dLon / 2) * Math.sin(dLon / 2);

                        const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
                        return R * c; // Distance in km
                    }

                    function calculateTotalDistance(data) {
                        let totalDistance = 0;

                        for (let i = 1; i < data.length; i++) {
                            const lat1 = parseFloat(data[i - 1].latitude);
                            const lon1 = parseFloat(data[i - 1].longitude);
                            const lat2 = parseFloat(data[i].latitude);
                            const lon2 = parseFloat(data[i].longitude);

                            totalDistance += haversineDistance(lat1, lon1, lat2, lon2);
                        }

                        return totalDistance;
                    }
                    // Get the selected exchange reason
                    const exchangeReason = formData.get("exchange_reason");
                    const gpsCoordinates = @json($gps_provider_path_coordinates);
                    const geoCoordinates = @json($path_coordinates);

                    if (exchangeReason) {
                        const gpsCoordinatesTotalKm = calculateTotalDistance(gpsCoordinates).toFixed(2);
                        const geoCoordinatesTotalKm = calculateTotalDistance(geoCoordinates).toFixed(2);

                        if (parseFloat(gpsCoordinatesTotalKm)) {
                            formData.append("total_distance", parseFloat(gpsCoordinatesTotalKm));
                        } else {
                            formData.append("total_distance", parseFloat(geoCoordinatesTotalKm));
                        }
                        // Only add exchange_date if exchange_reason is selected
                        formData.append("exchange_date", new Date().toISOString());
                    }

                    $.ajax({
                        url: $(form).attr("action"),
                        type: $(form).attr('method'),
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                        data: formData,
                        contentType: false,
                        processData: false,
                        crossDomain: true,
                        async: true,
                        success: function(response) {
                            console.log("response:", response)
                            if (response?.success) {
                                $("#submit-button").html('Saved');
                                toastr.success(response?.message);

                                if (response?.return_url) {
                                    setTimeout(function() {
                                        window.location.href = response?.return_url;
                                    }, 2000);
                                }
                            } else {
                                toastr.error(response?.message ?? 'Unable to update trip!');
                                $("#submit-button").html('Save');
                            }
                            $("#submit-button").attr('disabled', false);
                        },
                        error: function(xhr, status, error) {
                            console.log("error:", error);
                            toastr.error(error);
                            $("#submit-button").attr('disabled', false);
                            $("#submit-button").html('Save');
                        }
                    });
                }
            });
        });
    </script>
