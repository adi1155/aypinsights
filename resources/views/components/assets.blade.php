{{--
  Loads Vite assets when built; falls back to CDN + static CSS/JS when manifest is missing (XAMPP without npm build).
--}}
@php
    $entries = $entries ?? ['resources/css/app.css', 'resources/js/app.js'];
    $hasVite = file_exists(public_path('build/manifest.json'));
@endphp

@if ($hasVite)
    @vite($entries)
@else
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: ['selector', '[data-theme="dark"]'],
            theme: {
                extend: {
                    fontFamily: { sans: ['DM Sans', 'ui-sans-serif', 'system-ui', 'sans-serif'] },
                },
            },
        };
    </script>
    <link rel="stylesheet" href="{{ asset('css/executive.css') }}">
    @if (in_array('resources/js/app.js', $entries, true))
        <script src="{{ asset('js/executive.js') }}" defer></script>
    @endif
@endif
{{-- page-progress.js loaded in layout head when using executive layout --}}
