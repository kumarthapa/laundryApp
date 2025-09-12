@extends("layouts/contentNavbarLayout")

@section("title", "No Data Found")

@section("content")
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="alert alert-warning d-flex" role="alert">
            <span class="badge badge-center rounded-pill bg-warning border-label-warning p-3 me-2">
                <i class='bx bx-error-circle bx-lg fs-6'></i>
            </span>
            <div class="d-flex flex-column ps-1">
                <h6 class="alert-heading d-flex align-items-center mb-1">No Data Found!!</h6>
                @if (isset($message))
                    <span>{{ $message }}</span>
                @endif
            </div>
        </div>
    </div>
@endsection
