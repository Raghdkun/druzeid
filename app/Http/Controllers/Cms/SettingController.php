<?php

declare(strict_types=1);

namespace App\Http\Controllers\Cms;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\SiteChrome;
use App\Support\Locale;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Response;

class SettingController extends Controller
{
    public function edit(): Response
    {
        return inertia('cms/settings/edit', [
            'settings' => [
                'siteShortName' => Setting::get('site.short_name', []),
                'siteTagline' => Setting::get('site.tagline', []),
                'footerNote' => Setting::get('footer.note', []),
                'contactEmail' => Setting::get('contact.email', ''),
                'contactPhones' => Setting::get('contact.phones', []),
                'contactAddress' => Setting::get('contact.address'),
                'socialLinks' => Setting::get('social.links', []),
            ],
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
        $validated = $request->validate([
            'siteShortName' => ['array'],
            'siteShortName.*' => ['nullable', 'string', 'max:120'],
            'siteTagline' => ['array'],
            'siteTagline.*' => ['nullable', 'string', 'max:300'],
            'footerNote' => ['array'],
            'footerNote.*' => ['nullable', 'string', 'max:500'],
            'contactEmail' => ['nullable', 'email:rfc', 'max:190'],
            'contactPhones' => ['array'],
            'contactPhones.*.label' => ['array'],
            'contactPhones.*.value' => ['required', 'string', 'max:40'],
            'contactAddress' => ['nullable', 'string', 'max:500'],
            'socialLinks' => ['array'],
            'socialLinks.*.platform' => ['required', 'string', 'max:40'],
            'socialLinks.*.url' => ['required', 'url', 'max:2048'],
        ]);

        Setting::put('site.short_name', $this->pruneLocales($validated['siteShortName'] ?? []), 'general');
        Setting::put('site.tagline', $this->pruneLocales($validated['siteTagline'] ?? []), 'general');
        Setting::put('footer.note', $this->pruneLocales($validated['footerNote'] ?? []), 'footer');
        Setting::put('contact.email', $validated['contactEmail'] ?? null, 'contact');
        Setting::put('contact.phones', array_values($validated['contactPhones'] ?? []), 'contact');
        Setting::put('contact.address', $validated['contactAddress'] ?? null, 'contact');
        Setting::put('social.links', array_values($validated['socialLinks'] ?? []), 'social');

        SiteChrome::flush();

        return back()->with('success', __('Settings saved.'));
    }

    /**
     * @param  array<string, string|null>  $values
     * @return array<string, string>
     */
    private function pruneLocales(array $values): array
    {
        return array_filter(
            $values,
            fn ($value, $key) => Locale::isSupported((string) $key) && filled($value),
            ARRAY_FILTER_USE_BOTH
        );
    }
}
