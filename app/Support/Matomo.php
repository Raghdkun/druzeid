<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\Crypt;
use Throwable;

/**
 * Matomo analytics configuration, edited in the CMS settings.
 *
 * The site URL and site id are public (they ship in the front-end tracker), but
 * the Reporting API token is a SECRET: it is stored encrypted at rest and only
 * ever read server-side (by the analytics dashboard controller). It is never
 * sent to the browser and never placed in a URL — the reporting requests POST
 * it in the body.
 */
final class Matomo
{
    /**
     * Tracking is active only when explicitly enabled and the public tracker
     * has both a site URL and a site id.
     */
    public static function enabled(): bool
    {
        return (bool) Setting::get('matomo.enabled', false)
            && self::url() !== null
            && self::siteId() !== null;
    }

    /**
     * The Matomo base URL, always with a trailing slash (e.g.
     * `https://matomo.druze.id/matomo/`), or null when unset.
     */
    public static function url(): ?string
    {
        $url = Setting::get('matomo.url');

        if (! is_string($url) || trim($url) === '') {
            return null;
        }

        return rtrim(trim($url), '/').'/';
    }

    public static function siteId(): ?int
    {
        $id = Setting::get('matomo.site_id');

        return is_numeric($id) ? (int) $id : null;
    }

    /**
     * The Matomo Tag Manager container id (e.g. `rkUM09pP`), or null. When set,
     * the front-end loads the MTM container instead of the plain tracker — the
     * container itself owns the tracking configuration in Matomo.
     */
    public static function containerId(): ?string
    {
        $id = Setting::get('matomo.container_id');

        if (! is_string($id) || trim($id) === '') {
            return null;
        }

        return trim($id);
    }

    /**
     * The Matomo origin (scheme://host[:port]) for the CSP allowlist, or null.
     */
    public static function host(): ?string
    {
        $url = self::url();

        if ($url === null) {
            return null;
        }

        $parts = parse_url($url);

        if (! isset($parts['scheme'], $parts['host'])) {
            return null;
        }

        return $parts['scheme'].'://'.$parts['host'].(isset($parts['port']) ? ':'.$parts['port'] : '');
    }

    /**
     * The public tracker config passed to the front-end snippet, or null when
     * tracking is off. Contains no secret. When `containerId` is present the
     * front-end loads the Tag Manager container; otherwise the plain tracker.
     *
     * @return array{url: string, siteId: int, containerId: string|null}|null
     */
    public static function trackerConfig(): ?array
    {
        if (! self::enabled()) {
            return null;
        }

        return [
            'url' => (string) self::url(),
            'siteId' => (int) self::siteId(),
            'containerId' => self::containerId(),
        ];
    }

    public static function hasToken(): bool
    {
        return self::token() !== null;
    }

    /**
     * The decrypted Reporting API token. SERVER-SIDE ONLY — never expose this to
     * a response that reaches the browser.
     */
    public static function token(): ?string
    {
        $stored = Setting::get('matomo.token');

        if (! is_string($stored) || $stored === '') {
            return null;
        }

        try {
            $token = Crypt::decryptString($stored);
        } catch (Throwable) {
            return null;
        }

        return $token === '' ? null : $token;
    }

    /**
     * Whether the analytics dashboard can query the Reporting API — needs the
     * site URL, site id and a token (tracking being enabled is not required to
     * read reports).
     */
    public static function reportingReady(): bool
    {
        return self::url() !== null && self::siteId() !== null && self::hasToken();
    }

    /**
     * The Reporting API endpoint.
     */
    public static function apiUrl(): ?string
    {
        $url = self::url();

        return $url === null ? null : $url.'index.php';
    }

    /**
     * Encrypt a raw token for storage.
     */
    public static function encryptToken(string $raw): string
    {
        return Crypt::encryptString($raw);
    }
}
