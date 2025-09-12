@php
    $width = $width ?? "200";
    $height = $height ?? "150";

@endphp
@if (config("company_logo"))
    <img src="{{ asset(config("company_logo")) }}" width="{{ $width }}" style="object-fit: contain"
        height="{{ $height }}" alt="Company Logo">
@else
    <img src="{{ asset("logos/logo.png") }}" width="{{ $width }}" style="object-fit: contain"
        height="{{ $height }}" alt="Company Logo">
@endif
