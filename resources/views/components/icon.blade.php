@props(['name' => 'circle', 'class' => 'h-5 w-5'])

@php
    $paths = [
        'dashboard' => '<rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/>',
        'cases' => '<path d="M3.75 7.5A2.25 2.25 0 0 1 6 5.25h3.4l1.6 2h7A2.25 2.25 0 0 1 20 9.5v7.75A2.25 2.25 0 0 1 17.75 19.5H6A2.25 2.25 0 0 1 3.75 17.25V7.5Z"/>',
        'reports' => '<path d="M4.5 19.5h15"/><path d="M7 16V10"/><path d="M12 16V5"/><path d="M17 16v-4"/>',
        'users' => '<circle cx="9" cy="8.5" r="3"/><path d="M3.75 19.5a5.25 5.25 0 0 1 10.5 0"/><path d="M16.5 6.2a3 3 0 0 1 0 5.6"/><path d="M17.5 14.4a4.5 4.5 0 0 1 2.75 4.1"/>',
        'officer' => '<path d="M12 3 5.25 5.8v5.1c0 4.1 2.7 7.4 6.75 8.7 4.05-1.3 6.75-4.6 6.75-8.7V5.8L12 3Z"/><circle cx="12" cy="10" r="2"/><path d="M9 15.5c.8-1.4 1.8-2.1 3-2.1s2.2.7 3 2.1"/>',
        'suspect' => '<circle cx="10" cy="9" r="3.25"/><path d="M4.5 19a5.5 5.5 0 0 1 11 0"/><circle cx="17" cy="9.5" r="2.5"/><path d="m16.4 15.2 1.3 1.3 2.3-2.6"/>',
        'victim' => '<path d="M12 20s-7.25-4.35-7.25-9.25A4.25 4.25 0 0 1 12 7.75a4.25 4.25 0 0 1 7.25 3C19.25 15.65 12 20 12 20Z"/><path d="M9 12h6"/><path d="M12 9v6"/>',
        'evidence' => '<rect x="3.75" y="4.5" width="16.5" height="4" rx="1"/><path d="M5.25 8.5V18a2 2 0 0 0 2 2h9.5a2 2 0 0 0 2-2V8.5"/><path d="M10 12.5h4"/>',
        'profile' => '<circle cx="12" cy="8.25" r="3.5"/><path d="M5.25 20a6.75 6.75 0 0 1 13.5 0"/>',
        'logout' => '<path d="M14.25 8.25V5.5A2.25 2.25 0 0 0 12 3.25H6.75A2.25 2.25 0 0 0 4.5 5.5v13a2.25 2.25 0 0 0 2.25 2.25H12a2.25 2.25 0 0 0 2.25-2.25v-2.75"/><path d="M10 12h10"/><path d="m17 9 3 3-3 3"/>',
        'search' => '<circle cx="10.75" cy="10.75" r="6"/><path d="m15.5 15.5 4 4"/>',
        'filter' => '<path d="M4 6h16"/><path d="M7 12h10"/><path d="M10 18h4"/>',
        'plus' => '<path d="M12 5v14"/><path d="M5 12h14"/>',
        'download' => '<path d="M12 3.75v11"/><path d="m8 11 4 4 4-4"/><path d="M4.5 17.25v1.5A1.75 1.75 0 0 0 6.25 20.5h11.5a1.75 1.75 0 0 0 1.75-1.75v-1.5"/>',
        'printer' => '<path d="M7 8.25V4.5h10v3.75"/><path d="M6.75 17.25H5A1.75 1.75 0 0 1 3.25 15.5v-4.5A2.75 2.75 0 0 1 6 8.25h12a2.75 2.75 0 0 1 2.75 2.75v4.5a1.75 1.75 0 0 1-1.75 1.75h-1.75"/><rect x="6.75" y="13.5" width="10.5" height="6.25" rx="1"/>',
        'eye' => '<path d="M2.75 12S6 6.75 12 6.75 21.25 12 21.25 12 18 17.25 12 17.25 2.75 12 2.75 12Z"/><circle cx="12" cy="12" r="2.75"/>',
        'clock' => '<circle cx="12" cy="12" r="8.25"/><path d="M12 7.5V12l3 2"/>',
        'check' => '<path d="m5 12.5 4.5 4.5L19 7.5"/>',
        'arrow-right' => '<path d="M4.5 12h15"/><path d="m15 7.5 4.5 4.5-4.5 4.5"/>',
        'menu' => '<path d="M4 7h16"/><path d="M4 12h16"/><path d="M4 17h16"/>',
        'close' => '<path d="M6 6l12 12"/><path d="M18 6 6 18"/>',
        'file' => '<path d="M13.5 3.75H7A2.25 2.25 0 0 0 4.75 6v12A2.25 2.25 0 0 0 7 20.25h10A2.25 2.25 0 0 0 19.25 18V9.5l-5.75-5.75Z"/><path d="M13.5 3.75V9.5H19.25"/><path d="M8.5 13h7"/><path d="M8.5 16.5h4.5"/>',
        'clipboard' => '<rect x="5.75" y="4.75" width="12.5" height="15.5" rx="2"/><path d="M9 4.75V3.5h6v1.25"/><path d="M9 10h6"/><path d="M9 13.5h6"/><path d="M9 17h3.5"/>',
        'scale' => '<path d="M12 4v16"/><path d="M6 20h12"/><path d="M4.5 8h15"/><path d="M7.5 8 4.5 14h6L7.5 8Z"/><path d="M16.5 8 13.5 14h6l-3-6Z"/>',
        'lock' => '<rect x="5.5" y="10.5" width="13" height="9" rx="2"/><path d="M8.5 10.5V8a3.5 3.5 0 0 1 7 0v2.5"/><path d="M12 14v2"/>',
        'mail' => '<rect x="3.5" y="5.75" width="17" height="12.5" rx="2"/><path d="m4.5 7 7.5 5.5L19.5 7"/>',
        'shield' => '<path d="M12 3 5.25 5.8v5.1c0 4.1 2.7 7.4 6.75 8.7 4.05-1.3 6.75-4.6 6.75-8.7V5.8L12 3Z"/><path d="m9.25 11.75 2 2 3.5-3.75"/>',
        'chart' => '<path d="M4 19.5h16"/><path d="M6.5 16V9"/><path d="M11 16V5"/><path d="M15.5 16v-5"/><path d="M20 16v-8"/>',
        'inbox' => '<path d="M4.5 13.5 6.75 5.25h10.5l2.25 8.25v4.5A2.25 2.25 0 0 1 17.25 20.25h-10.5A2.25 2.25 0 0 1 4.5 18v-4.5Z"/><path d="M4.5 13.5h4.25l1 2.25h4.5l1-2.25h4.25"/>',
        'alert' => '<path d="M12 4.5 3.75 19h16.5L12 4.5Z"/><path d="M12 9.75v4"/><path d="M12 16.5h.01"/>',
        'info' => '<circle cx="12" cy="12" r="8.25"/><path d="M12 11v5"/><path d="M12 8h.01"/>',
        'sun' => '<circle cx="12" cy="12" r="4"/><path d="M12 2.75v2"/><path d="M12 19.25v2"/><path d="m4.22 4.22 1.42 1.42"/><path d="m18.36 18.36 1.42 1.42"/><path d="M2.75 12h2"/><path d="M19.25 12h2"/><path d="m4.22 19.78 1.42-1.42"/><path d="m18.36 5.64 1.42-1.42"/>',
        'moon' => '<path d="M20 14.5A8.25 8.25 0 0 1 9.5 4 8.25 8.25 0 1 0 20 14.5Z"/>',
        'circle' => '<circle cx="12" cy="12" r="8.25"/>',
    ];
@endphp

<svg class="{{ $class }}" {{ $attributes->except('class')->merge(['fill' => 'none', 'stroke' => 'currentColor', 'stroke-width' => '1.8', 'stroke-linecap' => 'round', 'stroke-linejoin' => 'round', 'viewBox' => '0 0 24 24', 'aria-hidden' => 'true']) }}>
    {!! $paths[$name] ?? $paths['circle'] !!}
</svg>
