<?php

declare(strict_types=1);

namespace App\Http\Controllers\Cms;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\SiteChrome;
use App\Support\Locale;
use App\Support\Theme;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Response;

/**
 * Site-wide theme editor (admin-only): brand colours and per-language fonts.
 * Values equal to the shipped defaults are stored as null so the injected CSS
 * stays empty until something is genuinely customised.
 */
class ThemeController extends Controller
{
    public function edit(): Response
    {
        return inertia('cms/theme/edit', [
            'theme' => Theme::forEditor(),
            'defaults' => Theme::DEFAULTS,
            'fonts' => array_map(
                fn (string $key) => ['key' => $key, 'label' => Theme::FONTS[$key]['label']],
                array_keys(Theme::FONTS),
            ),
            'locales' => array_map(fn (string $code) => [
                'code' => $code,
                'name' => Locale::meta($code)['name'],
                'dir' => Locale::dir($code),
                'default' => $code === Locale::default(),
            ], Locale::codes()),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $fontKeys = array_keys(Theme::FONTS);

        $validated = $request->validate([
            'colors' => ['array'],
            'colors.*' => ['nullable', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'fonts' => ['array'],
            'fonts.*.display' => ['nullable', Rule::in($fontKeys)],
            'fonts.*.body' => ['nullable', Rule::in($fontKeys)],
        ]);

        foreach (Theme::COLOR_KEYS as $key) {
            $value = $validated['colors'][$key] ?? null;
            $isCustom = is_string($value) && strtolower($value) !== strtolower(Theme::DEFAULTS[$key]);
            Setting::put("theme.color.{$key}", $isCustom ? strtolower($value) : null, 'theme');
        }

        foreach (Locale::codes() as $code) {
            $display = $validated['fonts'][$code]['display'] ?? null;
            $body = $validated['fonts'][$code]['body'] ?? null;
            Setting::put("theme.font.{$code}.display", $display !== null && $display !== Theme::DEFAULT_DISPLAY ? $display : null, 'theme');
            Setting::put("theme.font.{$code}.body", $body !== null && $body !== Theme::DEFAULT_BODY ? $body : null, 'theme');
        }

        SiteChrome::flush();

        return back()->with('success', __('Theme saved.'));
    }
}
