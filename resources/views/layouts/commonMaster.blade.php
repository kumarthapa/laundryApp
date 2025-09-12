<!DOCTYPE html>
<html class="light-style layout-navbar-fixed layout-compact layout-menu-fixed" dir="ltr" data-theme="theme-default"
    data-assets-path="{{ asset("/assets") . "/" }}" data-base-url="{{ url("/") }}">

<head>
    <meta charset="utf-8" />
    <meta name="viewport"
        content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />

    <title>@yield("title") | {{ config("company_name") ?? config("variables.organizationName") }}</title>
    <meta name="description"
        content="{{ config("variables.templateDescription") ? config("variables.templateDescription") : "" }}" />
    <meta name="keywords"
        content="{{ config("variables.templateKeyword") ? config("variables.templateKeyword") : "" }}">
    <!-- laravel CRUD token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <!-- Canonical SEO -->
    <link rel="canonical" href="{{ config("variables.productPage") ? config("variables.productPage") : "" }}">
    <!-- Favicon assets/img/favicon/favicon.ico -->
    <link rel="icon" type="image/x-icon" href="{{ asset(config("company_brand_logo")) }}" />

    <!-- Include Styles -->
    @include("layouts/sections/styles")

    <!-- Include Scripts for customizer, helper, analytics, config -->
    @include("layouts/sections/scriptsIncludes")

    <style>
        .menu-vertical {
            width: 17.25rem;
        }

        .app-brand .layout-menu-toggle i {
            color: #fff;
            width: 1.5rem;
            height: 1.5rem;
            transition: all .3s ease-in-out;
            line-height: 1.05;
        }
    </style>
</head>

<body>
    <!-- Layout Content -->
    @yield("layoutContent")
    <!--/ Layout Content -->

    <!-- Include Scripts -->
    @include("layouts/sections/scripts")
    @yield("page-scripts")

    @if (session("error"))
        <script>
            toastr.error('{{ session("error") }}');
        </script>
    @endif
    @if (session("success"))
        <script>
            toastr.success('{{ session("success") }}');
        </script>
    @endif
    @if (session("warning"))
        <script>
            toastr.warning('{{ session("warning") }}');
        </script>
    @endif

    <script>
        $(document).ready(function() {
            // Set initial sidebar state from localStorage
            var collapsed = localStorage.getItem('sidebarCollapsed') === 'true';
            setSidebarState(collapsed);

            // Handle sidebar toggle click
            $('.layout-menu-toggle').click(function() {
                collapsed = !collapsed;
                setSidebarState(collapsed);
                localStorage.setItem('sidebarCollapsed', collapsed);
            });

            // Enable: Handle hover to temporarily expand sidebar
            $('.layout-menu').hover(
                function() {
                    // Mouse enters, and sidebar is collapsed
                    if (collapsed) {
                        $('.layout-navbar-fixed').removeClass('layout-menu-collapsed').addClass(
                            'layout-menu-expanded');
                    }
                },
                function() {
                    // Mouse leaves, return to collapsed state if needed
                    if (collapsed) {
                        $('.layout-navbar-fixed').addClass('layout-menu-collapsed').removeClass(
                            'layout-menu-expanded');
                    }
                }
            );

            // Helper function to set sidebar state
            function setSidebarState(collapsed) {
                if (collapsed) {
                    $('.layout-navbar-fixed').addClass('layout-menu-collapsed').removeClass(
                        'layout-menu-expanded');
                } else {
                    $('.layout-navbar-fixed').removeClass('layout-menu-collapsed').addClass(
                        'layout-menu-expanded');
                }
            }
        });
    </script>

</body>

</html>
