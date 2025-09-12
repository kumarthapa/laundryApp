<form method="GET" action="{{ route('tours.exportToursReport') }}" enctype="multipart/form-data" id="exportTourForm">
  @csrf
  <div class="modal fade" id="toursExportModal" tabindex="-1" aria-modal="true" role="dialog">
      <div class="modal-dialog modal-dialog-centered modal-md">
          <div class="modal-content">
              <div class="modal-header">
                  <h4 class="modal-title" id="modalCenterTitle">Tours Export</h4>
                  <button type="button" class="btn btn-label-danger p-2" data-bs-dismiss="modal" aria-label="Close">
                      <i class='bx bx-x'></i>
                  </button>
              </div>

              <div class="modal-body">
                  <!-- Date Range Picker -->
                  <div class="col-md-12 col-12 mb-3">
                      <label for="ToursExportDaterangePicker" class="form-label text-danger">Select Date Range</label>
                      <div class="input-group">
                          <input type="text" class="form-control" name="ToursExportDaterangePicker" id="ToursExportDaterangePicker" placeholder="DD/MM/YY - DD/MM/YY" />
                          <span class="input-group-text">
                              <i class="bx bxs-calendar"></i>
                          </span>
                      </div>
                  </div>

                  <!-- Customer Location Dropdown -->
                  <div class="col-md-12 col-12 mb-3" id="customerLocationDropdown">
                      <label for="customerLocation" class="form-label text-danger">Select Customer Location</label>
                      <select class="form-control select2" name="customer_location" id="customerLocation">
                          <option value="">Select Location</option>
                      </select>
                  </div>

                  <div class="mb-3">
                      <div class="alert alert-danger" role="alert" id="errorBox" style="display: none;"></div>
                  </div>
              </div>

              <div class="modal-footer">
                  <button type="button" class="btn btn-label-danger" data-bs-dismiss="modal">Cancel</button>
                  <button type="submit" class="btn btn-label-primary" id="export-submit-button">Export</button>
              </div>
          </div>
      </div>
  </div>
</form>

<script>
  $(document).ready(function () {
      $('#customerLocation').select2({
        dropdownParent: $('#customerLocationDropdown'),
        placeholder: "Select Location",
        allowClear: true // Allows clearing the selected option
      });
      // Fetch customer locations dynamically
      $.ajax({
          url: "{{ route('locations.list') }}", // Route to fetch locations
          type: "GET",
          success: function (response) {
            let res = JSON.parse(response) ?? null;
            let locations = res ? res?.data : null;
            let customerLocationDropdown = $("#customerLocation");
            customerLocationDropdown.empty().append('<option value="">Select Location</option>');

            if (locations && locations.length > 0) {
              locations.forEach(function (location) {
                customerLocationDropdown.append(`<option value="${location.location_code}">${location.name} - ${location.city}</option>`);
              });
            } else {
              customerLocationDropdown.append('<option value="">No locations available</option>');
            }
          },
          error: function () {
              console.error("Error fetching customer locations");
          }
      });

      $("#ToursExportDaterangePicker").on("cancel.daterangepicker", function(ev, picker) {
          $(this).val("");
      });

      // Form Submission Handling
      $("#exportTourForm").on("submit", function (event) {
          let dateRange = $("#ToursExportDaterangePicker").val();
          let customerLocation = $("#customerLocation").val();
          let $errorBox = $("#errorBox");
          let $submitButton = $("#export-submit-button");

          // Hide error box and show loading state
          $errorBox.hide();
          $submitButton.attr("disabled", true).html("Exporting...");

          // Simulate Export Process
          setTimeout(function () {
              $submitButton.attr("disabled", false).html("Export");
              toastr.success("Data exported successfully!");
              $("#toursExportModal").modal("hide");
          }, 2000);
      });
  });
</script>
