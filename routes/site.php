<?php

declare(strict_types=1);

use App\Http\Controllers\Site\ContactController;
use App\Http\Controllers\Site\NewsController;
use App\Http\Controllers\Site\PageController;
use App\Support\RoutePatterns;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public site routes
|--------------------------------------------------------------------------
|
| Registered once per locale by routes/web.php, which supplies the URL prefix
| and the `{locale}.` route-name prefix. Never require this file directly —
| route names would collide.
|
*/

Route::get('/', [PageController::class, 'home'])->name('home');

Route::post('contact', [ContactController::class, 'store'])
    ->middleware('throttle:5,1')
    ->name('contact.store');

// News — before the pages catch-all so `news` is not read as a page slug.
Route::get('news', [NewsController::class, 'index'])->name('news.index');
Route::get('news/{slug}', [NewsController::class, 'show'])
    ->where('slug', RoutePatterns::slug())
    ->name('news.show');

// Catch-all for CMS-managed pages. Registered last so every explicit route
// above wins, and constrained so it cannot shadow auth/CMS/locale URLs.
Route::get('{slug}', [PageController::class, 'show'])
    ->where('slug', RoutePatterns::slug())
    ->name('pages.show');
