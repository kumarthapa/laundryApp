<script type="text/javascript">
    $(document).ready(function() {
        let daysInMonth = moment().daysInMonth(); // Get the total days in the current month
        let defaultDays =
            "{{ isset($default_days) ? $default_days : 30 }}"; // Get default_days from Blade or use 30
        console.log(defaultDays);

        let startDate;
        if (defaultDays == 0) {
            startDate = moment(); // If default_days is 0, set today as the start date
        } else if (defaultDays == 30) {
            startDate = moment().subtract(30, 'days'); // Last 30 days
        } else if (defaultDays == 180) {
            startDate = moment().subtract(180, 'days'); // Last 180 days
        } else if (defaultDays == 360) {
            startDate = moment().subtract(360, 'days'); // Last 360 days
        } else {
            startDate = moment().subtract(daysInMonth, 'days'); // Current month days
        }

        $('input[name="{{ $name }}"]').daterangepicker({
            opens: "{{ isset($float) ? $float : 'right' }}",
            startDate: startDate,
            endDate: moment(), // Always end with today's date
            locale: {
                format: 'DD/MM/YYYY', // Set the desired date format here
            },
            ranges: {
                'Today': [moment(), moment()],
                'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                'Last 7 Days': [moment().subtract(6, 'days'), moment()],
                'Last 30 Days': [moment().subtract(29, 'days'), moment()],
                'This Month': [moment().startOf('month'), moment().endOf('month')],
                'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1,
                    'month').endOf('month')],
                'Current Year': [moment().startOf('year'), moment()],
                'Last Year': [moment().subtract(1, 'year').startOf('year'), moment().subtract(1, 'year')
                    .endOf('year')
                ]
            }
        });
    });
</script>
