<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Supported locales
    |--------------------------------------------------------------------------
    |
    | Mirrors the four languages the original WordPress site published through
    | Polylang. Arabic is the default and is served from the bare root with no
    | URL prefix; the rest are prefixed. `dir` drives the <html dir> attribute
    | and every logical-property decision in the UI.
    |
    */

    'supported' => [
        'ar' => [
            'name' => 'العربية',
            'english_name' => 'Arabic',
            'dir' => 'rtl',
            'prefix' => '',
            'regional' => 'ar_SY',
            'html_lang' => 'ar',
        ],
        'en' => [
            'name' => 'English',
            'english_name' => 'English',
            'dir' => 'ltr',
            'prefix' => 'en',
            'regional' => 'en_GB',
            'html_lang' => 'en-GB',
        ],
        'fr' => [
            'name' => 'Français',
            'english_name' => 'French',
            'dir' => 'ltr',
            'prefix' => 'fr',
            'regional' => 'fr_FR',
            'html_lang' => 'fr-FR',
        ],
        'nl' => [
            'name' => 'Nederlands',
            'english_name' => 'Dutch',
            'dir' => 'ltr',
            'prefix' => 'nl',
            'regional' => 'nl_NL',
            'html_lang' => 'nl-NL',
        ],
        'es' => [
            'name' => 'Español',
            'english_name' => 'Spanish',
            'dir' => 'ltr',
            'prefix' => 'es',
            'regional' => 'es_ES',
            'html_lang' => 'es',
        ],
        'he' => [
            'name' => 'עברית',
            'english_name' => 'Hebrew',
            'dir' => 'rtl',
            'prefix' => 'he',
            'regional' => 'he_IL',
            'html_lang' => 'he',
        ],
        'de' => [
            'name' => 'Deutsch',
            'english_name' => 'German',
            'dir' => 'ltr',
            'prefix' => 'de',
            'regional' => 'de_DE',
            'html_lang' => 'de',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Default & fallback
    |--------------------------------------------------------------------------
    |
    | `default` is served without a URL prefix. `fallback` is used when a
    | translatable field has no value for the active locale — the chain is
    | active -> fallback -> first non-empty -> null.
    |
    */

    'default' => 'ar',

    'fallback' => 'en',

];
