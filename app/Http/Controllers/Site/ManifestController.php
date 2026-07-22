<?php

declare(strict_types=1);

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Support\Branding;
use App\Support\Locale;
use Illuminate\Http\JsonResponse;

/**
 * The web app manifest, so the site can be installed to a phone home screen
 * with the organization's own icon and colours rather than a browser default.
 *
 * Built from the CMS branding settings; when no icon has been uploaded the
 * `icons` array is omitted and the browser falls back to the <link rel="icon">
 * tags in the document head.
 */
class ManifestController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $icons = [];

        foreach (Branding::icons() as $size => $url) {
            // 32px is a favicon, too small to be a useful install icon.
            if ($size < 180) {
                continue;
            }

            $icons[] = [
                'src' => $url,
                'sizes' => "{$size}x{$size}",
                'type' => 'image/png',
                'purpose' => 'any',
            ];
        }

        $manifest = array_filter([
            'name' => Branding::appName(),
            'short_name' => Branding::appName(),
            'description' => Branding::description(),
            'start_url' => Locale::path('home'),
            'scope' => '/',
            'display' => 'standalone',
            'background_color' => Branding::backgroundColor(),
            'theme_color' => Branding::themeColor(),
            'lang' => Locale::htmlLang(),
            'dir' => Locale::dir(),
            'icons' => $icons,
        ], fn ($value) => $value !== null && $value !== []);

        return response()
            ->json($manifest, 200, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            ->header('Content-Type', 'application/manifest+json');
    }
}
