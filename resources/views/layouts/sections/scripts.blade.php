<!-- BEGIN: Vendor JS-->
<script src="{{ asset("assets/vendor/libs/jquery/jquery.js") }}"></script>
<script src="{{ asset("assets/vendor/libs/popper/popper.js") }}"></script>
<script src="{{ asset("assets/vendor/js/bootstrap.js") }}"></script>
<script src="{{ asset("assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js") }}"></script>
<script src="{{ asset("assets/vendor/js/menu.js") }}"></script>
@yield("vendor-script")
<!-- END: Page Vendor JS-->
<!-- BEGIN: Theme JS-->
<script src="{{ asset("assets/js/main.js") }}"></script>
<script src="{{ asset("assets/js/jquery.validate.js") }}"></script>
<script src="{{ asset("assets/js/jquery.form.min.js") }}"></script>
<script src="{{ asset("assets/js/select2.js") }}"></script>
<script src="{{ asset("assets/js/toastr.min.js") }}"></script>
<script src="{{ asset("assets/js/datatables-bootstrap5.js") }}"></script>
<script src="{{ asset("assets/js/moment.min.js") }}"></script>
<script src="{{ asset("assets/js/daterangepicker.min.js") }}"></script>
<script src="{{ asset("assets/js/tagify.min.js") }}"></script>
<script src="{{ asset("assets/js/ajax-request.js") }}"></script>
<!-- END: Theme JS-->
<!-- Pricing Modal JS-->
@stack("pricing-script")
<!-- END: Pricing Modal JS-->
<!-- BEGIN: Page JS-->
@yield("page-script")
<!-- END: Page JS-->
<script>
    // Initialize all tooltips
    document.addEventListener('DOMContentLoaded', function() {
        var tooltipTriggerList = Array.from(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.forEach(function(tooltipTriggerEl) {
            new bootstrap.Tooltip(tooltipTriggerEl);
        });
    });
</script>
