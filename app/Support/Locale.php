<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Route;

/**
 * Single source of truth for locale metadata and localized route naming.
 *
 * Routes are registered once per locale with a `{locale}.` name prefix
 * (`ar.home`, `en.home`, …), so resolving a route for the active locale is
 * always `Locale::route('home')`.
 */
final class Locale
{
    /**
     * All supported locale codes, default first.
     *
     * @return list<string>
     */
    public static function codes(): array
    {
        return array_map('strval', array_keys(config('locales.supported')));
    }

    public static function default(): string
    {
        return config('locales.default');
    }

    public static function fallback(): string
    {
        return config('locales.fallback');
    }

    public static function isSupported(?string $code): bool
    {
        return $code !== null && array_key_exists($code, config('locales.supported'));
    }

    /**
     * @return array<string, mixed>
     */
    public static function meta(?string $code = null): array
    {
        $code = self::isSupported($code) ? $code : App::getLocale();

        return config("locales.supported.{$code}") ?? config('locales.supported.'.self::default());
    }

    public static function dir(?string $code = null): string
    {
        return self::meta($code)['dir'];
    }

    public static function isRtl(?string $code = null): bool
    {
        return self::dir($code) === 'rtl';
    }

    /**
     * URL prefix for a locale — empty string for the default locale.
     */
    public static function prefix(?string $code = null): string
    {
        return self::meta($code)['prefix'];
    }

    public static function htmlLang(?string $code = null): string
    {
        return self::meta($code)['html_lang'];
    }

    /**
     * The fallback chain used when resolving a translatable value.
     *
     * @return list<string>
     */
    public static function chain(?string $code = null): array
    {
        $code = self::isSupported($code) ? $code : App::getLocale();

        return array_values(array_unique([$code, self::fallback(), self::default()]));
    }

    /**
     * The registered route name for a bare name in a locale. The default
     * locale uses bare names; the others are namespaced.
     *
     * routeName('pages.show', 'ar') -> 'pages.show'
     * routeName('pages.show', 'en') -> 'en.pages.show'
     */
    public static function routeName(string $name, ?string $code = null): string
    {
        $code = self::isSupported($code) ? $code : App::getLocale();

        return $code === self::default() ? $name : "{$code}.{$name}";
    }

    /**
     * Resolve a bare route name against the active (or given) locale. Absolute
     * by default (needed for canonical/OG/sitemap URLs).
     *
     * Locale::route('pages.show', ['slug' => 'org-about-us']) -> https://host/org-about-us
     *
     * @param  array<string, mixed>  $parameters
     */
    public static function route(string $name, array $parameters = [], ?string $code = null, bool $absolute = true): string
    {
        return route(self::routeName($name, $code), $parameters, $absolute);
    }

    /**
     * A ROOT-RELATIVE internal URL (e.g. `/org-about-us`, `/en/org-about-us`).
     *
     * Use this for every on-site navigation href — nav, menus, cards, hero and
     * form actions. Inertia's client router only intercepts links whose origin
     * matches window.location, so an absolute URL built from a different host,
     * port or scheme than the visitor is on (APP_URL vs the real host, localhost
     * vs 127.0.0.1, http vs an https proxy) is treated as external and the
     * click does a full reload — or is blocked by the CSP. A relative URL is
     * always same-origin, so it always works.
     *
     * @param  array<string, mixed>  $parameters
     */
    public static function path(string $name, array $parameters = [], ?string $code = null): string
    {
        return self::route($name, $parameters, $code, false);
    }

    /**
     * The equivalent of the current URL in another locale, used by the
     * language switcher. Falls back to that locale's home page when the
     * current route has no counterpart (e.g. a 404 or an unnamed route).
     */
    public static function currentUrlIn(string $code): string
    {
        // Relative throughout so the language switcher stays same-origin and
        // Inertia intercepts it (see Locale::path).
        if (! self::isSupported($code)) {
            return '/';
        }

        $route = Route::current();
        $name = $route?->getName();

        if ($name === null) {
            return self::path('home', [], $code);
        }

        // Reduce the current route name to its bare form by stripping any
        // leading locale namespace, then re-resolve it in the target locale.
        $bare = $name;
        foreach (self::codes() as $other) {
            if ($other !== self::default() && str_starts_with($name, "{$other}.")) {
                $bare = substr($name, strlen($other) + 1);
                break;
            }
        }

        $target = self::routeName($bare, $code);

        if (! Route::has($target)) {
            return self::path('home', [], $code);
        }

        return route($target, $route->parameters(), false);
    }

    /**
     * Locale list shaped for the front-end language switcher.
     *
     * @return list<array{code: string, name: string, dir: string, active: bool, url: string}>
     */
    public static function switcher(): array
    {
        $active = App::getLocale();

        return array_map(fn (string $code) => [
            'code' => $code,
            'name' => self::meta($code)['name'],
            'dir' => self::dir($code),
            'active' => $code === $active,
            'url' => self::currentUrlIn($code),
        ], self::codes());
    }
}
