@extends("layouts/contentNavbarLayout")

@section("title", "Not Authorized")

@section("page-style")
    <!-- Page -->
    <link rel="stylesheet" href="{{ asset("assets/vendor/css/pages/page-misc.css") }}">
@endsection


@section("content")
    <!-- Error -->
    <div class="container-xxl container-p-y">
        {{-- <div class="misc-wrapper">
            <h2 class="mb-2 mx-2">Page Not Found :(</h2>
            <p class="mb-4 mx-2">Oops! 😖 The requested URL was not found on this server.</p>
            <a href="{{ url("/") }}" class="btn btn-primary">Back to home</a>
            <div class="mt-3">
                <img src="{{ asset("assets/img/illustrations/page-misc-error-light.png") }}" alt="page-misc-error-light"
                    width="500" class="img-fluid">
            </div>
        </div> --}}
        <div class="misc-wrapper">
            <h2 class="mb-2 mx-2">You are not authorized!</h2>
            <p class="mb-4 mx-2">You do not have permission to view this page using the credentials that you have provided
                while login. <br> Please contact your site administrator.</p>
            <a href="" class="btn btn-primary">Back to home</a>
            <div class="mt-5">
                <img src="https://demos.themeselection.com/sneat-bootstrap-html-laravel-admin-template/demo/assets/img/illustrations/girl-with-laptop-light.png"
                    alt="page-misc-not-authorized-light" width="450" class="img-fluid"
                    data-app-light-img="illustrations/girl-with-laptop-light.png"
                    data-app-dark-img="illustrations/girl-with-laptop-dark.png">
            </div>
        </div>
    </div>
    <!-- /Error -->
@endsection
