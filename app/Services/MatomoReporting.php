<?php

declare(strict_types=1);

namespace App\Services;

use App\Support\Matomo;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Server-side proxy for the Matomo Reporting API.
 *
 * The secret token is read from the encrypted setting and POSTed in the request
 * body (never a query string, so it stays out of URLs and logs). Responses are
 * cached briefly to keep the dashboard snappy and avoid hammering Matomo, and
 * every failure degrades to null so one unavailable report never breaks the
 * page. The token is never returned to the caller or the browser.
 */
class MatomoReporting
{
    private const TTL_MINUTES = 10;

    private const TIMEOUT_SECONDS = 8;

    public function available(): bool
    {
        return Matomo::reportingReady();
    }

    /**
     * Call a single Reporting API method. Returns the decoded JSON (array) or
     * null when reporting is not configured, the request fails, or Matomo
     * returns an error.
     *
     * @param  array<string, scalar>  $params
     * @return array<int|string, mixed>|null
     */
    public function get(string $method, array $params = []): ?array
    {
        if (! Matomo::reportingReady()) {
            return null;
        }

        $endpoint = (string) Matomo::apiUrl();
        $body = [
            'module' => 'API',
            'method' => $method,
            'format' => 'JSON',
            'idSite' => (int) Matomo::siteId(),
            'token_auth' => (string) Matomo::token(),
            ...$params,
        ];

        // Config hash busts the cache when the URL, site or token changes; the
        // token itself is never stored in the key in a readable form.
        $cacheKey = 'matomo.report.'.md5($method.serialize($params).$this->configHash());

        return Cache::remember($cacheKey, now()->addMinutes(self::TTL_MINUTES), function () use ($endpoint, $body) {
            try {
                $response = Http::asForm()
                    ->timeout(self::TIMEOUT_SECONDS)
                    ->post($endpoint, $body);
            } catch (Throwable) {
                return null;
            }

            if (! $response->successful()) {
                return null;
            }

            $json = $response->json();

            // Matomo signals API errors with {"result":"error","message":…}.
            if (! is_array($json) || ($json['result'] ?? null) === 'error') {
                return null;
            }

            return $json;
        });
    }

    private function configHash(): string
    {
        return md5((string) Matomo::url().Matomo::siteId().(string) Matomo::token());
    }
}
