@php
    $width = $width ?? "200";
    $height = $height ?? "150";

@endphp
{{-- asset("logos/logo-icon.png") --}}
@if (config("company_brand_logo"))
    <img src="{{ asset(config("company_brand_logo")) }}" width="{{ $width }}" style="object-fit: contain"
        height="{{ $height }}" alt="Brand logo">
@else
    <img src="{{ asset("logos/logo-icon.png") }}" width="{{ $width }}" style="object-fit: contain"
        height="{{ $height }}" alt="Brand logo">
@endif
