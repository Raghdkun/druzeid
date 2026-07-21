<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Support\Locale;
use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

/**
 * Applies the locale for the current route.
 *
 * The locale is passed explicitly by the route group in routes/web.php rather
 * than parsed out of the URL, so there is exactly one place that decides which
 * locale a route belongs to.
 */
class SetLocale
{
    public function handle(Request $request, Closure $next, ?string $locale = null): Response
    {
        $locale = Locale::isSupported($locale) ? $locale : Locale::default();

        App::setLocale($locale);
        CarbonImmutable::setLocale($locale);

        // Keep the locale attached to generated URLs so redirects and
        // signed URLs stay inside the active language.
        URL::defaults(['locale' => $locale]);

        $response = $next($request);

        $response->headers->set('Content-Language', $locale);

        return $response;
    }
}
