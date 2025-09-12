@extends("layouts/contentNavbarLayout")

@section("title", "Error Found")

@section("content")
    <div class="container-xxl container-p-y mx-auto">
        <div class="misc-wrapper">
            <h2 class="mb-2 mx-2">Error Page</h2>
            <p class="mb-4 mx-2">Oops! 😖 There was a error whie page is loaded.</p>
            <a href="{{ url("/") }}" class="btn btn-primary">Back to home</a>
            <div class="mt-3">
                <img src="{{ asset("assets/img/illustrations/page-misc-error.png") }}" alt="page-misc-error-light"
                    width="500" class="img-fluid">
            </div>
        </div>
    </div>
@endsection
