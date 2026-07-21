@php
    // Dark mode is a dashboard-only affordance. The public site and the staff
    // auth screens are a light, institutional canvas, so dark is applied only
    // within the CMS/account area.
    $allowDark = request()->is('cms', 'cms/*', 'settings', 'settings/*', 'dashboard');
@endphp
<!DOCTYPE html>
<html lang="{{ \App\Support\Locale::htmlLang() }}" dir="{{ \App\Support\Locale::dir() }}" @class(['dark' => $allowDark && ($appearance ?? 'system') == 'dark'])>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        {{-- Inline script to detect system dark mode preference and apply it immediately (dashboard only) --}}
        @if ($allowDark)
        <script nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}">
            (function() {
                const appearance = '{{ $appearance ?? "system" }}';

                if (appearance === 'system') {
                    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

                    if (prefersDark) {
                        document.documentElement.classList.add('dark');
                    }
                }
            })();
        </script>
        @endif

        {{-- Inline style to set the HTML background color based on our theme in app.css --}}
        <style>
            html {
                background-color: oklch(1 0 0);
            }

            html.dark {
                background-color: oklch(0.145 0 0);
            }
        </style>

        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="icon" href="/favicon.svg" sizes="any">
        <meta name="theme-color" content="#0057B8">

        @fonts

        {{-- SEO meta rendered server-side so social scrapers and non-JS
             crawlers (which don't run Inertia) see the correct per-page tags.
             The React <Head> keeps only the title, which Inertia updates on
             client-side navigation. --}}
        @php
            $meta = $page['props']['meta'] ?? null;
        @endphp
        @if ($meta)
            @if (!empty($meta['description']))
                <meta name="description" content="{{ $meta['description'] }}">
            @endif
            <link rel="canonical" href="{{ $meta['canonical'] }}">
            @foreach (($meta['alternates'] ?? []) as $alt)
                <link rel="alternate" hreflang="{{ $alt['locale'] }}" href="{{ $alt['href'] }}">
            @endforeach

            <meta property="og:type" content="website">
            <meta property="og:title" content="{{ $meta['title'] }}">
            @if (!empty($meta['description']))
                <meta property="og:description" content="{{ $meta['description'] }}">
            @endif
            <meta property="og:url" content="{{ $meta['canonical'] }}">
            <meta property="og:locale" content="{{ $meta['ogLocale'] ?? 'ar' }}">
            @if (!empty($meta['siteName']))
                <meta property="og:site_name" content="{{ $meta['siteName'] }}">
            @endif
            @if (!empty($meta['ogImage']))
                <meta property="og:image" content="{{ $meta['ogImage'] }}">
            @endif

            <meta name="twitter:card" content="{{ !empty($meta['ogImage']) ? 'summary_large_image' : 'summary' }}">
            <meta name="twitter:title" content="{{ $meta['title'] }}">
            @if (!empty($meta['description']))
                <meta name="twitter:description" content="{{ $meta['description'] }}">
            @endif
            @if (!empty($meta['ogImage']))
                <meta name="twitter:image" content="{{ $meta['ogImage'] }}">
            @endif

            @php
                $ld = [
                    '@context' => 'https://schema.org',
                    '@type' => 'Organization',
                    'name' => $meta['siteName'] ?? config('app.name'),
                    'url' => $meta['canonical'],
                ];
                if (! empty($meta['ogImage'])) {
                    $ld['logo'] = $meta['ogImage'];
                }
                if (! empty($meta['description'])) {
                    $ld['description'] = $meta['description'];
                }
            @endphp
            <script type="application/ld+json" nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}">{!! json_encode($ld, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
        @endif

        @viteReactRefresh
        @vite(['resources/css/app.css', 'resources/js/app.tsx', "resources/js/pages/{$page['component']}.tsx"])
        <x-inertia::head>
            <title>{{ $meta['title'] ?? config('app.name', 'Laravel') }}</title>
        </x-inertia::head>
    </head>
    <body class="font-sans antialiased">
        <x-inertia::app />
    </body>
</html>
