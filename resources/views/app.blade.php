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

        {{-- Icons, theme colour and manifest come from the CMS branding
             settings, falling back to the bundled emblem until an icon is
             uploaded there. --}}
        @php
            $brandIcons = \App\Support\Branding::icons();
            $fallbackIcon = \App\Support\Branding::FALLBACK_ICON;
        @endphp
        @if ($brandIcons !== [])
            <link rel="icon" type="image/png" sizes="32x32" href="{{ $brandIcons[32] ?? $fallbackIcon }}">
            <link rel="icon" type="image/png" sizes="192x192" href="{{ $brandIcons[192] ?? $fallbackIcon }}">
            <link rel="apple-touch-icon" sizes="180x180" href="{{ $brandIcons[180] ?? $fallbackIcon }}">
        @else
            <link rel="icon" href="{{ $fallbackIcon }}" type="image/svg+xml">
            <link rel="icon" href="{{ $fallbackIcon }}" sizes="any">
        @endif
        <link rel="manifest" href="{{ url('/site.webmanifest') }}">
        <meta name="theme-color" content="{{ \App\Support\Branding::themeColor() }}">

        {{-- Matomo analytics — public site pages only (never the CMS/auth), and
             only when configured + enabled in the dashboard. Values are
             JSON-encoded so nothing from the settings can break out of the
             script, and the script carries the CSP nonce. When a Tag Manager
             container id is set the MTM container loads (it owns the tracking
             configuration); otherwise the plain tracker is used. --}}
        @php $matomo = \App\Support\Matomo::trackerConfig(); @endphp
        @if ($matomo && \Illuminate\Support\Str::startsWith($page['component'] ?? '', 'site/'))
        @if ($matomo['containerId'])
        <script nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}">
            var _mtm = window._mtm = window._mtm || [];
            _mtm.push({'mtm.startTime': (new Date().getTime()), 'event': 'mtm.Start'});
            (function () {
                var d = document, g = d.createElement('script'), s = d.getElementsByTagName('script')[0];
                g.async = true; g.src = @json($matomo['url'].'js/container_'.$matomo['containerId'].'.js');
                s.parentNode.insertBefore(g, s);
            })();
        </script>
        @else
        <script nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}">
            var _paq = window._paq = window._paq || [];
            _paq.push(['trackPageView']);
            _paq.push(['enableLinkTracking']);
            (function () {
                var u = @json($matomo['url']);
                _paq.push(['setTrackerUrl', u + 'matomo.php']);
                _paq.push(['setSiteId', @json((string) $matomo['siteId'])]);
                var d = document, g = d.createElement('script'), s = d.getElementsByTagName('script')[0];
                g.async = true; g.src = u + 'matomo.js'; s.parentNode.insertBefore(g, s);
            })();
        </script>
        @endif
        @endif

        @fonts

        {{-- SEO meta rendered server-side so social scrapers and non-JS
             crawlers (which don't run Inertia) see the correct per-page tags.
             The React <Head> keeps only the title, which Inertia updates on
             client-side navigation. --}}
        @php
            $meta = $page['props']['meta'] ?? null;
            // Fall back to the site-wide description and share image from the
            // CMS branding settings when a page defines none of its own.
            $metaDescription = filled($meta['description'] ?? null)
                ? $meta['description']
                : \App\Support\Branding::description();
            $metaOgImage = filled($meta['ogImage'] ?? null)
                ? $meta['ogImage']
                : \App\Support\Branding::ogImageUrl();
        @endphp
        @if ($meta)
            @if (filled($metaDescription))
                <meta name="description" content="{{ $metaDescription }}">
            @endif
            <link rel="canonical" href="{{ $meta['canonical'] }}">
            @foreach (($meta['alternates'] ?? []) as $alt)
                <link rel="alternate" hreflang="{{ $alt['locale'] }}" href="{{ $alt['href'] }}">
            @endforeach

            <meta property="og:type" content="website">
            <meta property="og:title" content="{{ $meta['title'] }}">
            @if (filled($metaDescription))
                <meta property="og:description" content="{{ $metaDescription }}">
            @endif
            <meta property="og:url" content="{{ $meta['canonical'] }}">
            <meta property="og:locale" content="{{ $meta['ogLocale'] ?? 'ar' }}">
            @if (!empty($meta['siteName']))
                <meta property="og:site_name" content="{{ $meta['siteName'] }}">
            @endif
            @if (filled($metaOgImage))
                <meta property="og:image" content="{{ $metaOgImage }}">
            @endif

            <meta name="twitter:card" content="{{ filled($metaOgImage) ? 'summary_large_image' : 'summary' }}">
            <meta name="twitter:title" content="{{ $meta['title'] }}">
            @if (filled($metaDescription))
                <meta name="twitter:description" content="{{ $metaDescription }}">
            @endif
            @if (filled($metaOgImage))
                <meta name="twitter:image" content="{{ $metaOgImage }}">
            @endif

            @php
                $ld = [
                    '@context' => 'https://schema.org',
                    '@type' => 'Organization',
                    'name' => $meta['siteName'] ?? config('app.name'),
                    'url' => $meta['canonical'],
                ];
                if (filled($metaOgImage)) {
                    $ld['logo'] = $metaOgImage;
                }
                if (filled($metaDescription)) {
                    $ld['description'] = $metaDescription;
                }
            @endphp
            <script type="application/ld+json" nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}">{!! json_encode($ld, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
        @endif

        @viteReactRefresh
        @vite(['resources/css/app.css', 'resources/js/app.tsx', "resources/js/pages/{$page['component']}.tsx"])

        {{-- Admin-configured theme (brand colours + per-language fonts) as CSS
             variable overrides. Rendered after the app stylesheet so it wins,
             and only emitted when something is actually customised. Carries the
             CSP nonce like the other inline styles/scripts. --}}
        @php $themeCss = \App\Support\Theme::cssVariables(); @endphp
        @if ($themeCss !== '')
        <style nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}">:root { {!! $themeCss !!} }</style>
        @endif

        <x-inertia::head>
            <title>{{ $meta['title'] ?? config('app.name', 'Laravel') }}</title>
        </x-inertia::head>
    </head>
    <body class="font-sans antialiased">
        <x-inertia::app />
    </body>
</html>
