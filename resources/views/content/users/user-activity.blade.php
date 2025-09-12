@extends("layouts/contentNavbarLayout")

@section("title", " Users - Activity")
@section("page-style")
    {{-- <link rel="stylesheet" href="{{ asset("assets/vendor/libs/libs/flatpickr.min.css") }}"> --}}
    <link rel="stylesheet" href="{{ asset("assets/vendor/libs/flatpickr/flatpickr.css") }}">
    <link rel="stylesheet" href="{{ asset("assets/css/datatables.bootstrap5.css") }}">
@endsection
@section("content")
    <div class="row">
        <div class="col-md-12">
            <div class="card mb-6">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h5 class="card-title">{{ $user->fullname }}</h5>
                    {{-- <div class="">
                        <label for="flatpickr-datetime" class="form-label">Date</label>
                        <input type="datetime" class="form-control flatpickr-input datetime_picker" placeholder="YYYY-MM-DD"
                            id="activity_date" name="trip_end_datetime">
                    </div> --}}
                    <div>
                        <div class="row">
                            <div class="col-md-12 col-12 mb-6">
                                <label for="Label Name">Filter By Date Range</label>
                                <div class="input-group date">
                                    <input class="form-control" type="text" name="userActivityDaterangePicker"
                                        placeholder="DD/MM/YY" id="userActivityDaterangePicker" />
                                    <span class="input-group-text">
                                        <i class='bx bxs-calendar'></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-body px-3 my-4">
                    <ul class="timeline mb-0">

                    </ul>
                </div>
            </div>
        </div>
    </div>
@endsection

@section("page-script")
    <script src="{{ asset("assets/js/fileinput.min.js") }}"></script>
    <script src="{{ asset("assets/js/custom-js.js") }}"></script>
    <script src="{{ asset("assets/vendor/libs/flatpickr/flatpickr.js") }}"></script>
    @include("content.common.scripts.daterangePicker", [
        "float" => "left",
        "name" => "userActivityDaterangePicker",
    ])
    <script type="text/javascript">
        $(document).ready(function() {
            console.log("user activit");
            flatpickr(".datetime_picker", {
                weekNumbers: true,
                defaultDate: new Date()

            });

            let dateRange = $("#userActivityDaterangePicker")

            function getData() {
                $(".timeline").html();


                $.ajax({
                    url: '{{ route("users.activityLogs") }}',
                    type: "POST",
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr(
                            'content')
                    },
                    data: {
                        id: "{{ $id }}",
                        date: dateRange.val()
                    },

                    success: function(response) {
                        console.log("response", response);

                        loadActivity(response.data);
                        if (response?.success) {
                            // toastr.success(response?.message);
                        } else {
                            toastr.error(response?.message ??
                                'Unable to load Activity!');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error("Error during fetching user activity:",
                            error);
                        loadActivity(null);
                        toastr.error("Error during activity");
                    }
                });
            }

            function loadActivity(data) {
                console.log("data", data);
                let html = '';
                data?.forEach(activity => {
                    html += `<li class="timeline-item timeline-item-transparent">
                            <span class="timeline-point timeline-point-primary"></span>
                            <div class="timeline-event">
                                <div class="timeline-header mb-3">
                                    <h6 class="mb-0">${activity?.message}</h6>
                                    <small class="text-muted">${activity?.datetime}</small>
                                </div>
                                <p class="mb-2">
                                </p>
                                <div class="d-flex align-items-center mb-2">
                                    <div class="badge bg-lighter rounded d-flex align-items-center">
                                        <i class="bx bxl-windows bx-xs text-info me-4"></i>
                                        <span class="h6 mb-0 text-body">${activity?.application}</span>
                                    </div>
                                </div>
                            </div>
                        </li>`;
                });

                $(".timeline").html(html);
                $(".card-title").text('TimeLine for - ' + "{{ $user->fullname }}");

            }

            dateRange.on("change", function() {
                getData();
            });

            getData()
        });
    </script>
@endsection
