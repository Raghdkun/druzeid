<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Setting;
use App\Support\Branding;
use App\Support\Locale;
use App\Support\Translate;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;

/**
 * Assembles the navigation, footer and site-wide settings that every public
 * page needs. Cached per locale because the chrome is identical across pages
 * and changes only when an editor saves a menu or setting.
 */
class SiteChrome
{
    /**
     * @return array<string, mixed>
     */
    public function props(?string $locale = null): array
    {
        $locale ??= App::getLocale();

        return Cache::rememberForever("site.chrome.{$locale}", fn () => [
            'menus' => [
                'primary' => $this->menu(Menu::PRIMARY, $locale),
                'footerSections' => $this->menu(Menu::FOOTER_SECTIONS, $locale),
                'footerOrganization' => $this->menu(Menu::FOOTER_ORGANIZATION, $locale),
            ],
            'settings' => $this->settings($locale),
            'contactAction' => Locale::path('contact.store', [], $locale),
        ]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function menu(string $key, string $locale): array
    {
        $menu = Menu::query()->where('key', $key)->first();

        if ($menu === null) {
            return [];
        }

        return array_values(
            $menu->rootItems()
                ->get()
                ->map(fn (MenuItem $item) => $item->toNavItem($locale))
                ->all()
        );
    }

    /**
     * Public-facing settings only — anything an editor stores that the site
     * chrome or contact page renders.
     *
     * @return array<string, mixed>
     */
    protected function settings(string $locale): array
    {
        $keys = [
            'site.tagline', 'site.short_name',
            'contact.email', 'contact.phones', 'contact.address',
            'social.links', 'footer.note',
        ];

        $settings = [];

        foreach ($keys as $key) {
            $settings[$key] = Translate::resolve(Setting::get($key), $locale);
        }

        // Resolved branding the header needs; null keeps the built-in emblem.
        $settings['site.logo_url'] = Branding::logoUrl();

        return $settings;
    }

    /**
     * Called whenever a menu or setting is saved.
     */
    public static function flush(): void
    {
        foreach (Locale::codes() as $code) {
            Cache::forget("site.chrome.{$code}");
        }

        // Pages, menus and settings all feed the sitemap; rebuild it next fetch.
        Cache::forget('sitemap.xml');
    }
}
