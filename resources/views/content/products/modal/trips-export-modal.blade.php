<div class="modal fade" id="trips_export_modal" tabindex="-1" aria-modal="true" role="dialog">
    <form method="GET" action="{{ route("trips.exportTourTripsReport", ["code" => $tour->tour_id]) }}"
        id="exportTourTripsForm">

        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title" id="modalCenterTitle">Trips Export</h4>
                    <button type="button" class="btn btn-label-danger p-2" data-bs-dismiss="modal" aria-label="Close">
                        <i class="bx bx-x"></i>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="col-md-12 col-12 mb-6">
                        <label for="tripsExportDaterangePicker" class="form-label text-danger">Select Date Range</label>
                        <div class="input-group date">
                            <input class="form-control" type="text" name="tripsExportDaterangePicker"
                                placeholder="DD/MM/YY" id="tripsExportDaterangePicker" />
                            <span class="input-group-text">
                                <i class="bx bxs-calendar"></i>
                            </span>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="alert alert-danger" role="alert" id="errorBox" style="display: none;"></div>
                    </div>
                    <div class="result"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-label-danger" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-label-primary" id="export-submit-button">Export</button>
                </div>
            </div>
        </div>
    </form>
</div>
<script>
    $(document).ready(function() {
        $("#exportTourTripsForm").on("submit", function(event) {
            let $form = $(this);
            let dateRange = $("#tripsExportDaterangePicker").val();
            let $errorBox = $("#errorBox");
            let $submitButton = $("#export-submit-button");

            // Validate the date range input
            if (!dateRange) {
                event.preventDefault(); // Prevent form submission
                $errorBox.text("Please select a date range.").show();
                return;
            }

            // Hide error message and disable the button to prevent multiple clicks
            $errorBox.hide();
            $submitButton.attr("disabled", true).html("Exporting...");

            // Wait a few seconds to simulate download completion
            setTimeout(function() {
                $submitButton.attr("disabled", false).html("Export");
                toastr.success("Data exported successfully!");
                $("#trips_export_modal").modal("hide");
            }, 2000); // Adjust this time to match your export process duration
        });
    });
</script>
