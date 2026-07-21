<?php

namespace App\Http\Middleware;

use App\Support\Locale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $request->user(),
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',

            // i18n — every component reads direction from here rather than
            // sniffing the DOM, so SSR and hydration agree.
            //
            // These MUST be lazy: share() runs in the global web middleware,
            // which executes before the route-level SetLocale, so evaluating
            // App::getLocale() here eagerly would capture the default locale.
            // Closures are resolved by Inertia at response time — after the
            // locale has been applied.
            'locale' => fn () => App::getLocale(),
            'dir' => fn () => Locale::dir(),
            'locales' => fn () => Locale::switcher(),
            'translations' => fn () => $this->translations(App::getLocale()),

            // One-request flash for the public contact form and CMS saves.
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
        ];
    }

    /**
     * UI strings for the active locale, merged over the fallback locale so a
     * partially translated language still renders readable chrome.
     *
     * @return array<string, string>
     */
    protected function translations(string $locale): array
    {
        return Cache::flexible(
            "translations.{$locale}",
            [300, 3600],
            fn () => array_merge(
                $this->loadLanguageFile(Locale::fallback()),
                $this->loadLanguageFile($locale),
            ),
        );
    }

    /**
     * @return array<string, string>
     */
    protected function loadLanguageFile(string $locale): array
    {
        $path = lang_path("{$locale}.json");

        if (! is_file($path)) {
            return [];
        }

        return json_decode((string) file_get_contents($path), true) ?: [];
    }
}
