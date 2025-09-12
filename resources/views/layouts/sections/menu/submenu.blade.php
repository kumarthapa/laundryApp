<ul class="submenu">
    @foreach ($menu as $submenu)
        @php
            $activeClass = null;
            $currentRouteName = Route::currentRouteName();

            if (isset($submenu->slug)) {
                if (is_array($submenu->slug)) {
                    foreach ($submenu->slug as $slug) {
                        if (str_starts_with($currentRouteName, $slug)) {
                            $activeClass = "active open";
                        }
                    }
                } else {
                    if (str_starts_with($currentRouteName, $submenu->slug)) {
                        $activeClass = "active open";
                    }
                }
            }
        @endphp

        <li class="submenu-item {{ $activeClass }}">
            <a href="{{ isset($submenu->slug) ? url($submenu->slug) : "javascript:void(0);" }}" class="submenu-link"
                @if (!empty($submenu->target)) target="_blank" @endif>
                @isset($submenu->icon)
                    <i class="{{ $submenu->icon }}"></i>
                @endisset
                {{ isset($submenu->module_id) ? __($submenu->module_id) : "" }}
            </a>

            {{-- Recursive submenu --}}
            @isset($submenu->submenu)
                @include("layouts.sections.menu.submenu", ["menu" => $submenu->submenu])
            @endisset
        </li>
    @endforeach
</ul>
