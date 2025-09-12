@extends("layouts/contentNavbarLayout")

@section("title", "Error")
@section("page-style")
    <style>
        .misc-wrapper {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            min-height: calc(100vh - 3rem);
            text-align: center
        }
    </style>
    <link rel="stylesheet" href="{{ asset("assets/css/datatables.bootstrap5.css") }}">
@section("content")

    {{-- <div class="row"> --}}

    {{-- <div class="col-12"> --}}
    <div class="misc-wrapper">
        <h1 class="mb-2 mx-2" style="line-height: 6rem;font-size: 6rem;">404</h1>
        <h4 class="mb-2 mx-2">Error ⚠️</h4>
        <p class="mb-6 mx-2">we couldn't find the page you are looking for</p>
        {{-- <a href="https://demos.themeselection.com/sneat-bootstrap-html-laravel-admin-template/demo-1"
            class="btn btn-primary">Back to home</a> --}}
        <div class="mt-6">
            <img src="{{ asset("assets/img/illustrations/page-misc-error-light.png") }}" alt="page-misc-error-light"
                width="500" class="img-fluid" data-app-dark-img="illustrations/page-misc-error-dark.png"
                data-app-light-img="illustrations/page-misc-error-light.png">
        </div>
    </div>
    {{-- </div> --}}
    {{-- </div> --}}
@endsection
