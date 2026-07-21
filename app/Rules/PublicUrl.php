<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * A link destination safe to render as an href: a relative path, an in-page
 * anchor, or an absolute URL on an allowed scheme. Rejects javascript:, data:
 * and any other scheme, so a content editor cannot store a script-URL that
 * would fire if a link were ever rendered through a plain <a> rather than
 * Inertia's <Link>.
 */
class PublicUrl implements ValidationRule
{
    private const ALLOWED_SCHEMES = ['http', 'https', 'mailto', 'tel'];

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            return;
        }

        if (! is_string($value)) {
            $fail('الرابط غير صالح.');

            return;
        }

        // Relative paths and in-page anchors carry no scheme and are safe.
        if (str_starts_with($value, '/') || str_starts_with($value, '#')) {
            return;
        }

        $scheme = strtolower((string) parse_url($value, PHP_URL_SCHEME));

        if (! in_array($scheme, self::ALLOWED_SCHEMES, true)) {
            $fail('يجب أن يكون الرابط مساراً داخلياً أو رابطاً من نوع http أو https أو mailto أو tel.');
        }
    }
}
