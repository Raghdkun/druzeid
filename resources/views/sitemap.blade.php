<?php echo '<?xml version="1.0" encoding="UTF-8"?>'."\n"; ?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
        xmlns:xhtml="http://www.w3.org/1999/xhtml"
        xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">
@foreach ($urls as $url)
    <url>
        <loc>{{ $url['loc'] }}</loc>
@if ($url['lastmod'])
        <lastmod>{{ $url['lastmod'] }}</lastmod>
@endif
        <changefreq>{{ $url['changefreq'] }}</changefreq>
        <priority>{{ $url['priority'] }}</priority>
@foreach ($url['alternates'] as $alt)
        <xhtml:link rel="alternate" hreflang="{{ $alt['hreflang'] }}" href="{{ $alt['href'] }}" />
@endforeach
@foreach ($url['images'] as $image)
        <image:image>
            <image:loc>{{ $image['loc'] }}</image:loc>
@if ($image['title'])
            <image:title>{{ $image['title'] }}</image:title>
@endif
        </image:image>
@endforeach
    </url>
@endforeach
</urlset>
