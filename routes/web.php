<?php

declare(strict_types=1);

use App\Http\Controllers\Site\SitemapController;
use App\Http\Middleware\SetLocale;
use App\Support\Locale;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| SEO — global, not locale-prefixed
|--------------------------------------------------------------------------
*/

Route::get('sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');
Route::get('robots.txt', [SitemapController::class, 'robots'])->name('robots');

/*
|--------------------------------------------------------------------------
| Public site — one registration per locale
|--------------------------------------------------------------------------
|
| Arabic is served from the bare root, the others from /en, /fr, /nl. Each
| group gets a `{locale}.` route-name prefix, so `Locale::route('home')`
| resolves against the active language.
|
| Non-default locales are registered first: the Arabic group owns the root
| catch-all, and registering it last guarantees /en can never be mistaken
| for a page slug.
|
*/

$default = Locale::default();
$ordered = [...array_diff(Locale::codes(), [$default]), $default];

foreach ($ordered as $code) {
    // The default locale uses bare route names (home, pages.show, …) so
    // that framework and kit code calling route('home') resolves to it;
    // the others are namespaced (en.home, fr.pages.show, …).
    Route::prefix(Locale::prefix($code))
        ->name($code === $default ? '' : "{$code}.")
        ->middleware(SetLocale::class.":{$code}")
        ->group(base_path('routes/site.php'));
}

/*
|--------------------------------------------------------------------------
| Authenticated area
|--------------------------------------------------------------------------
|
| The CMS and account settings are not locale-prefixed — they render in the
| application default locale for the signed-in editor.
|
*/

// /dashboard is the conventional post-login target used by the kit and by
// Fortify's redirects; point it at the CMS overview, which is the real
// dashboard for this application. Gated so guests land on login directly.
Route::redirect('dashboard', '/cms')->middleware(['auth', 'verified'])->name('dashboard');

require __DIR__.'/cms.php';
require __DIR__.'/settings.php';
