<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Support\Matomo;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;

/**
 * Baseline security response headers.
 *
 * The Content-Security-Policy keeps `default-src` to 'self' so no external
 * origin can load script. `script-src` is nonce-based (no 'unsafe-inline'): the
 * per-request nonce set on Vite covers both the injected asset tags and the
 * two inline scripts in app.blade.php, so even if a rich-text sink ever slipped
 * past sanitisation the injected inline script would not execute. Together with
 * the server-side HTML sanitisation of CMS content this is the main defence
 * against injected script.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Skip non-HTML responses (assets, JSON, files) — these headers only
        // matter for documents.
        $contentType = (string) $response->headers->get('Content-Type');
        if ($contentType !== '' && ! str_contains($contentType, 'text/html')) {
            return $response;
        }

        // When Matomo is on, its origin is allowed to load the tracker script,
        // send the tracking beacon, and serve the fallback tracking image.
        $matomo = Matomo::enabled() ? Matomo::host() : null;

        $directives = [
            "default-src 'self'",
            $this->scriptSrc($matomo),
            "style-src 'self' 'unsafe-inline'",
            'img-src '.trim("'self' data: blob: ".(string) $matomo),
            "font-src 'self' data:",
            $this->connectSrc($matomo),
            "media-src 'self' blob:",
            "worker-src 'self' blob:",
            "object-src 'none'",
            "frame-ancestors 'self'",
            "form-action 'self'",
            "base-uri 'self'",
        ];

        // Only force HTTPS upgrades when already on HTTPS. Emitting this over
        // plain HTTP (local development) rewrites same-origin form posts and
        // fetches to an https:// origin that isn't listening, which breaks
        // login and every other request.
        if ($request->isSecure()) {
            $directives[] = 'upgrade-insecure-requests';
        }

        $csp = implode('; ', $directives);

        $headers = [
            'Content-Security-Policy' => $csp,
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'SAMEORIGIN',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
            'Permissions-Policy' => 'camera=(), microphone=(), geolocation=(), interest-cohort=()',
            'X-Permitted-Cross-Domain-Policies' => 'none',
        ];

        foreach ($headers as $name => $value) {
            $response->headers->set($name, $value);
        }

        // HSTS only over HTTPS, so local http development is unaffected.
        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }

    /**
     * Nonce-based script-src. Falls back to 'unsafe-inline' only if no nonce is
     * available. When the Vite dev server is running (`npm run dev`), its origin
     * and 'unsafe-eval' are allowed so HMR works — never the case in production.
     */
    private function scriptSrc(?string $matomo = null): string
    {
        $nonce = Vite::cspNonce();
        $src = "script-src 'self' blob:".($nonce !== null ? " 'nonce-{$nonce}'" : " 'unsafe-inline'");

        if ($matomo !== null) {
            $src .= " {$matomo}";
        }

        if (Vite::isRunningHot()) {
            $src .= " 'unsafe-eval' ".$this->hotOrigin();
        }

        return $src;
    }

    private function connectSrc(?string $matomo = null): string
    {
        $src = "connect-src 'self'";

        if ($matomo !== null) {
            $src .= " {$matomo}";
        }

        if (Vite::isRunningHot()) {
            $origin = $this->hotOrigin();
            $ws = str_replace(['http://', 'https://'], ['ws://', 'wss://'], $origin);
            $src .= " {$origin} {$ws}";
        }

        return $src;
    }

    /**
     * The Vite dev-server origin from the hot file (dev only).
     */
    private function hotOrigin(): string
    {
        $hot = @file_get_contents(public_path('hot'));

        if (! is_string($hot) || trim($hot) === '') {
            return 'http://localhost:5173';
        }

        $url = parse_url(trim($hot));

        return ($url['scheme'] ?? 'http').'://'.($url['host'] ?? 'localhost').(isset($url['port']) ? ':'.$url['port'] : '');
    }
}
