<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Regex constraints for the public page catch-all.
 *
 * The site serves CMS pages from the root (`/org-about-us`), so the catch-all
 * must not swallow framework, auth, CMS or locale-prefixed URLs. Keeping the
 * reserved list here means there is one place to update when a top-level route
 * is added.
 */
final class RoutePatterns
{
    /**
     * Top-level segments that must never resolve to a CMS page.
     *
     * @var list<string>
     */
    private const RESERVED = [
        'login', 'logout', 'register',
        'forgot-password', 'reset-password', 'confirm-password',
        'verify-email', 'two-factor-challenge', 'user',
        'dashboard', 'settings', 'cms', 'admin',
        'storage', 'build', 'vendor', 'up', 'api',
    ];

    /**
     * Slug pattern: lowercase kebab/unicode word characters, excluding both
     * the reserved segments above and every locale prefix.
     */
    public static function slug(): string
    {
        $reserved = array_merge(
            self::RESERVED,
            array_filter(array_map(
                fn (string $code) => Locale::prefix($code),
                Locale::codes()
            ))
        );

        $excluded = implode('|', array_map('preg_quote', $reserved));

        // Negative lookahead rejects reserved words as a *whole* segment,
        // so a legitimate slug like "login-help" still resolves.
        return '^(?!(?:'.$excluded.')$)[\p{L}\p{N}][\p{L}\p{N}\-_]*$';
    }
}
