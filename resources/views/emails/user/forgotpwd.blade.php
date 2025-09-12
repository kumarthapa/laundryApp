@extends("layouts/blankLayout")

@section("title", "")

@section("page-style")
@endsection

@section("content")
    <div class="container-xxl">
        <div class="authentication-wrapper authentication-basic container-p-y">
            <div class="authentication-inner py-4">

                <div class="card">
                    <div class="card-body">
                        <div class="app-brand justify-content-center">
                            <p class="app-brand-link gap-2">
                                <span class="app-brand-logo demo">@include("_partials.logo", ["width" => 25, "withbg" => "#696cff"])</span>
                            </p>
                        </div>
                        <h4 class="mb-2">{{ $otp }} is your verification code to reset the password.</h4>

                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
