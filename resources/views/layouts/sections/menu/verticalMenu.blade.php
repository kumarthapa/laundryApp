<aside id="layout-menu" class="layout-menu menu-vertical menu bg-menu-theme">
    <div class="app-brand demo">
        <a href="{{ url("/") }}" class="app-brand-link">
            <span class="app-brand-logo demo">
                @include("_partials.logo-icon", ["width" => 50, "withbg" => "var(--bs-primary)"])
            </span>
            <span class="text-md app-brand-text fw-bold ms-2">{{ config("company_brand_name") }}</span>
        </a>
        <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large ms-auto">
            <i class="bx bx-chevron-left bx-sm d-flex align-items-center justify-content-center"></i>
        </a>
    </div>

    <div class="menu-inner-shadow"></div>

    {{-- Main Menu List --}}
    <ul id="menu-inner-list" class="menu-inner h-auto overflow-auto py-1">
        @foreach ($menuData as $menu)
            {{-- Menu Header --}}
            @if (isset($menu->menuHeader))
                <li class="menu-header small text-uppercase">
                    <span class="menu-header-text">{{ __($menu->menuHeader) }}</span>
                </li>

                {{-- Menu Item with permission --}}
            @elseif (isset($menu->module_id) && \App\Helpers\UtilityHelper::CheckModulePermissions($menu->module_id))
                @php
                    $activeClass = null;
                    $currentRouteName = Route::currentRouteName();
                    $routeParts = explode(".", $currentRouteName);

                    if (in_array($menu->slug, $routeParts)) {
                        $activeClass = "active";
                    } elseif (isset($menu->submenu)) {
                        if (is_array($menu->slug)) {
                            foreach ($menu->slug as $slug) {
                                if (str_starts_with($currentRouteName, $slug)) {
                                    $activeClass = "active open";
                                }
                            }
                        } else {
                            if (str_starts_with($currentRouteName, $menu->slug)) {
                                $activeClass = "active open";
                            }
                        }
                    }
                @endphp

                <li class="menu-item {{ $activeClass }}">
                    <a href="{{ isset($menu->slug) ? url($menu->slug) : "javascript:void(0);" }}"
                        class="{{ isset($menu->submenu) ? "menu-link menu-toggle" : "menu-link" }}"
                        @if (!empty($menu->target)) target="_blank" @endif>
                        @isset($menu->icon)
                            <i class="menu-icon tf-icons {{ $menu->icon }}"></i>
                        @endisset
                        <div>{{ isset($menu->module_id) ? __("modules." . $menu->module_id) : "" }}</div>

                        @isset($menu->badge)
                            <div class="badge bg-{{ $menu->badge[0] }} rounded-pill ms-auto">{{ $menu->badge[1] }}</div>
                        @endisset
                    </a>

                    {{-- Recursive submenu inclusion --}}
                    @isset($menu->submenu)
                        @include("layouts.sections.menu.submenu", ["menu" => $menu->submenu])
                    @endisset
                </li>
            @endif
        @endforeach
    </ul>
</aside>
