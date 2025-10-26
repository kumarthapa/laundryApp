@extends('layouts/blankLayout')

@section('title', 'Forgot Password Basic - Pages')

@section('page-style')
    <!-- Page -->
    <link rel="stylesheet" href="{{ asset('assets/vendor/css/pages/page-auth.css') }}">
@endsection

@section('content')
    <div class="container-xxl">
        <div class="authentication-wrapper authentication-basic container-p-y">
            <div class="authentication-inner py-4">

                <!-- Forgot Password -->
                <div class="card">
                    <div class="card-body">
                        <div class="container-xxl container-p-y">

                            <div class="misc-wrapper">
                                <h2 class="mb-2 mx-2">You are not authorized!</h2>
                                <p class="mb-4 mx-2">You do not have permission to edit password.<br> Please contact your
                                    site administrator.</p>
                                <a href="{{ url('/auth/login') }}" class="btn btn-primary">Back to login</a>
                                <div class="mt-5">
                                    <img src="https://demos.themeselection.com/sneat-bootstrap-html-laravel-admin-template/demo/assets/img/illustrations/girl-with-laptop-light.png"
                                        alt="page-misc-not-authorized-light" width="450" class="img-fluid"
                                        data-app-light-img="illustrations/girl-with-laptop-light.png"
                                        data-app-dark-img="illustrations/girl-with-laptop-dark.png">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- /Forgot Password -->
            </div>
        </div>
    </div>
@endsection
