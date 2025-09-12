    {{-- View details modal --}}
    <div class="modal fade" id="vendorAdvanceViewDetailsModal" tabindex="-1" aria-modal="true" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title" id="modalCenterTitle"> Vendor Advance View Details</h4>
                    <button type="button" class="btn btn-label-danger p-2" data-bs-dismiss="modal" aria-label="Close">
                        <i class='bx bx-x'></i>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">Recipient Signature: <span id="view_recipients_signature"></span></h5>
                            <span class="card-subtitle">Delivery Date: <span id="view_delivery_date"></span></span>
                        </div>

                        <div class="card-body">
                            <div id="pod_images_wrapper" class="row gy-3">
                                {{-- Images will be inserted here dynamically --}}
                            </div>
                            <div id="pod_download_btn" class="me-1"></div>
                        </div>
                    </div>

                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">Vendor Advance Information</h5>
                            <span class="card-subtitle">Detailed breakdown of the selected advance request</span>
                        </div>

                        <div class="table-responsive">
                            <table class="table-bordered table">
                                <tbody>
                                    <tr>
                                        <th>Tour ID</th>
                                        <td id="view_tour_id"></td>
                                    </tr>
                                    <tr>
                                        <th>Trip ID</th>
                                        <td id="view_trip_id"></td>
                                    </tr>
                                    <tr>
                                        <th>Required Amount</th>
                                        <td id="view_required_amount"></td>
                                    </tr>
                                    <tr>
                                        <th>Approved Amount</th>
                                        <td id="view_approved_amount"></td>
                                    </tr>
                                    <tr>
                                        <th>Purpose</th>
                                        <td id="view_request_purpose"></td>
                                    </tr>
                                    <tr>
                                        <th>Station Name</th>
                                        <td id="view_station_name"></td>
                                    </tr>
                                    <tr>
                                        <th>Vehicle Number</th>
                                        <td id="view_vehicle_number"></td>
                                    </tr>
                                    <tr>
                                        <th>Driver Code</th>
                                        <td id="view_driver_code"></td>
                                    </tr>
                                    <tr>
                                        <th>Customer Code</th>
                                        <td id="view_customer_code"></td>
                                    </tr>
                                    <tr>
                                        <th>Comments</th>
                                        <td id="view_comments"></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-label-danger" data-bs-dismiss="modal">Close</button>
                        {{-- <button type="button" id="vendor_advance_approve" class="btn btn-label-primary">Approve</button> --}}
                    </div>

                </div>
            </div>
        </div>
