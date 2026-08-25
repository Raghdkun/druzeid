<?php

declare(strict_types=1);

namespace App\Http\Controllers\Cms;

use App\Http\Controllers\Controller;
use App\Models\Media;
use App\Models\Setting;
use App\Services\SiteChrome;
use App\Services\SiteIcons;
use App\Support\Branding;
use App\Support\Locale;
use App\Support\Matomo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Response;

class SettingController extends Controller
{
    public function __construct(private readonly SiteIcons $icons) {}

    public function edit(): Response
    {
        return inertia('cms/settings/edit', [
            'settings' => [
                'siteShortName' => Setting::get('site.short_name', []),
                'siteTagline' => Setting::get('site.tagline', []),
                'siteSeoDescription' => Setting::get('site.seo_description', []),
                'footerNote' => Setting::get('footer.note', []),
                'contactEmail' => Setting::get('contact.email', ''),
                'contactWhatsapp' => Setting::get('contact.whatsapp', ''),
                'contactPhones' => Setting::get('contact.phones', []),
                'contactAddress' => Setting::get('contact.address'),
                'socialLinks' => Setting::get('social.links', []),
                'iconMediaId' => Setting::get('site.icon_media_id'),
                'logoMediaId' => Setting::get('site.logo_media_id'),
                'ogImageMediaId' => Setting::get('site.og_image_media_id'),
                'themeColor' => Branding::themeColor(),
                // Matomo — the public config, plus a boolean for the secret
                // token (its value is never sent to the browser).
                'matomoEnabled' => (bool) Setting::get('matomo.enabled', false),
                'matomoUrl' => Setting::get('matomo.url', ''),
                'matomoSiteId' => Setting::get('matomo.site_id', ''),
                'matomoContainerId' => Setting::get('matomo.container_id', ''),
                'matomoTokenSet' => Matomo::hasToken(),
                // Visible-in-switcher locale whitelist (defaults to all).
                'enabledLocales' => Setting::get('locales.enabled', Locale::codes()),
            ],
            'iconPreview' => Branding::icons()[192] ?? null,
            'mediaLibrary' => $this->mediaLibrary(),
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
            'siteSeoDescription' => ['array'],
            'siteSeoDescription.*' => ['nullable', 'string', 'max:320'],
            'footerNote' => ['array'],
            'footerNote.*' => ['nullable', 'string', 'max:500'],
            'contactEmail' => ['nullable', 'email:rfc', 'max:190'],
            'contactWhatsapp' => ['nullable', 'string', 'max:40', 'regex:/^\+?[0-9\s-]+$/'],
            'contactPhones' => ['array'],
            'contactPhones.*.label' => ['array'],
            // Same digit/format constraint as the WhatsApp field, so a phone
            // number can only ever be a phone number (defence in depth for the
            // tel: links the contact bubble and footer build from it).
            'contactPhones.*.value' => ['required', 'string', 'max:40', 'regex:/^\+?[0-9()\s-]+$/'],
            'contactAddress' => ['nullable', 'string', 'max:500'],
            'socialLinks' => ['array'],
            'socialLinks.*.platform' => ['required', 'string', 'max:40'],
            'socialLinks.*.url' => ['required', 'url', 'max:2048'],
            'iconMediaId' => ['nullable', 'integer', 'exists:media,id'],
            'logoMediaId' => ['nullable', 'integer', 'exists:media,id'],
            'ogImageMediaId' => ['nullable', 'integer', 'exists:media,id'],
            'themeColor' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'matomoEnabled' => ['boolean'],
            'matomoUrl' => ['nullable', 'url', 'max:2048'],
            'matomoSiteId' => ['nullable', 'integer', 'min:1'],
            // Tag Manager container id — alphanumeric only (e.g. `rkUM09pP`), so
            // it can only ever be a safe path segment in the container URL.
            'matomoContainerId' => ['nullable', 'string', 'max:64', 'regex:/^[A-Za-z0-9]+$/'],
            // The token is optional on every save: blank keeps the stored one,
            // a value replaces it, and the clear flag removes it.
            'matomoToken' => ['nullable', 'string', 'max:191'],
            'matomoTokenClear' => ['boolean'],
            'enabledLocales' => ['array'],
            'enabledLocales.*' => [Rule::in(Locale::codes())],
        ]);

        Setting::put('site.short_name', $this->pruneLocales($validated['siteShortName'] ?? []), 'general');
        Setting::put('site.tagline', $this->pruneLocales($validated['siteTagline'] ?? []), 'general');
        Setting::put('site.seo_description', $this->pruneLocales($validated['siteSeoDescription'] ?? []), 'seo');
        Setting::put('footer.note', $this->pruneLocales($validated['footerNote'] ?? []), 'footer');
        Setting::put('contact.email', $validated['contactEmail'] ?? null, 'contact');
        Setting::put('contact.whatsapp', $validated['contactWhatsapp'] ?? null, 'contact');
        Setting::put('contact.phones', array_values($validated['contactPhones'] ?? []), 'contact');
        Setting::put('contact.address', $validated['contactAddress'] ?? null, 'contact');
        Setting::put('social.links', array_values($validated['socialLinks'] ?? []), 'social');
        Setting::put('locales.enabled', $this->normalizeEnabledLocales($validated['enabledLocales'] ?? null), 'general');

        Setting::put('site.logo_media_id', $validated['logoMediaId'] ?? null, 'branding');
        Setting::put('site.og_image_media_id', $validated['ogImageMediaId'] ?? null, 'branding');
        Setting::put('site.theme_color', $validated['themeColor'] ?? Branding::DEFAULT_THEME_COLOR, 'branding');

        Setting::put('matomo.enabled', $validated['matomoEnabled'] ?? false, 'analytics');
        Setting::put('matomo.url', $validated['matomoUrl'] ?? null, 'analytics');
        Setting::put('matomo.site_id', $validated['matomoSiteId'] ?? null, 'analytics');
        Setting::put('matomo.container_id', $validated['matomoContainerId'] ?? null, 'analytics');
        $this->syncMatomoToken($validated['matomoToken'] ?? null, $validated['matomoTokenClear'] ?? false);

        $this->syncIcon($validated['iconMediaId'] ?? null);

        SiteChrome::flush();

        return back()->with('success', __('Settings saved.'));
    }

    /**
     * Persist the Matomo Reporting API token, encrypted. A blank token leaves
     * the stored one untouched (so the value never has to round-trip to the
     * browser); the clear flag removes it.
     */
    private function syncMatomoToken(?string $token, bool $clear): void
    {
        if ($clear) {
            Setting::put('matomo.token', null, 'analytics');

            return;
        }

        if (filled($token)) {
            Setting::put('matomo.token', Matomo::encryptToken($token), 'analytics');
        }
    }

    /**
     * Regenerate the icon set when the chosen source image changes, and clear
     * it when the icon is removed. Icons are only rebuilt on an actual change,
     * since generating four sizes is comparatively expensive.
     */
    private function syncIcon(?int $mediaId): void
    {
        $current = Setting::get('site.icon_media_id');

        if ($current !== null && (int) $current === (int) $mediaId && Branding::hasCustomIcon()) {
            return;
        }

        $existing = Setting::get('site.icon_paths', []);
        $this->icons->delete(is_array($existing) ? $existing : []);

        $media = $mediaId === null ? null : Media::find($mediaId);
        $paths = $media === null ? [] : $this->icons->generate($media);

        Setting::put('site.icon_media_id', $paths === [] ? null : $mediaId, 'branding');
        Setting::put('site.icon_paths', $paths, 'branding');
    }

    /**
     * Images only — an icon, logo or share image is never a video.
     *
     * @return list<array<string, mixed>>
     */
    private function mediaLibrary(): array
    {
        return array_values(
            Media::query()
                ->where('kind', 'image')
                ->latest()
                ->take(200)
                ->get()
                ->map(fn (Media $media) => [
                    'id' => $media->id,
                    'url' => $media->url,
                    'kind' => $media->kind,
                    'poster' => $media->poster_url,
                    'alt' => $media->getTranslation('alt', Locale::default()) ?? '',
                    'width' => $media->width,
                    'height' => $media->height,
                    'needsAltReview' => $media->needs_alt_review,
                ])
                ->all()
        );
    }

    /**
     * The visible-locale whitelist, always kept in config order, always
     * including the default locale, and defaulting to all locales when none are
     * given (so the switcher is never empty).
     *
     * @param  list<string>|null  $codes
     * @return list<string>
     */
    private function normalizeEnabledLocales(?array $codes): array
    {
        $selected = $codes === null || $codes === [] ? Locale::codes() : $codes;

        $ordered = array_values(array_filter(
            Locale::codes(),
            fn (string $code) => in_array($code, $selected, true) || $code === Locale::default(),
        ));

        return $ordered;
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
