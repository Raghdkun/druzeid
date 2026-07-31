<?php

declare(strict_types=1);

use App\Http\Controllers\Cms\AnalyticsController;
use App\Http\Controllers\Cms\ArticleCategoryController;
use App\Http\Controllers\Cms\ArticleController;
use App\Http\Controllers\Cms\BackupController;
use App\Http\Controllers\Cms\MediaController;
use App\Http\Controllers\Cms\MenuController;
use App\Http\Controllers\Cms\OverviewController;
use App\Http\Controllers\Cms\PageController;
use App\Http\Controllers\Cms\SettingController;
use App\Http\Controllers\Cms\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| CMS routes
|--------------------------------------------------------------------------
|
| Not locale-prefixed — the dashboard renders in the application default
| locale for the signed-in editor, and edits every content locale through
| per-field tabs rather than separate URLs.
|
*/

Route::middleware(['auth', 'verified'])
    ->prefix('cms')
    ->name('cms.')
    ->group(function (): void {

        // Content — editors and admins.
        Route::middleware('can:manage-content')->group(function (): void {
            Route::get('/', [OverviewController::class, 'index'])->name('overview');

            // Bound by id (not the slug used publicly) so CMS URLs stay
            // stable when an editor changes a page's slug.
            Route::get('pages', [PageController::class, 'index'])->name('pages.index');
            Route::get('pages/create', [PageController::class, 'create'])->name('pages.create');
            Route::post('pages', [PageController::class, 'store'])->name('pages.store');
            Route::get('pages/{page:id}/edit', [PageController::class, 'edit'])->name('pages.edit');
            Route::put('pages/{page:id}', [PageController::class, 'update'])->name('pages.update');
            Route::delete('pages/{page:id}', [PageController::class, 'destroy'])->name('pages.destroy');

            Route::get('articles', [ArticleController::class, 'index'])->name('articles.index');
            Route::get('articles/create', [ArticleController::class, 'create'])->name('articles.create');
            Route::post('articles', [ArticleController::class, 'store'])->name('articles.store');
            Route::get('articles/{article:id}/edit', [ArticleController::class, 'edit'])->name('articles.edit');
            Route::put('articles/{article:id}', [ArticleController::class, 'update'])->name('articles.update');
            Route::delete('articles/{article:id}', [ArticleController::class, 'destroy'])->name('articles.destroy');

            Route::get('categories', [ArticleCategoryController::class, 'index'])->name('categories.index');
            Route::get('categories/create', [ArticleCategoryController::class, 'create'])->name('categories.create');
            Route::post('categories', [ArticleCategoryController::class, 'store'])->name('categories.store');
            Route::get('categories/{category:id}/edit', [ArticleCategoryController::class, 'edit'])->name('categories.edit');
            Route::put('categories/{category:id}', [ArticleCategoryController::class, 'update'])->name('categories.update');
            Route::delete('categories/{category:id}', [ArticleCategoryController::class, 'destroy'])->name('categories.destroy');

            Route::get('media', [MediaController::class, 'index'])->name('media.index');
            Route::post('media', [MediaController::class, 'store'])->name('media.store');
            Route::put('media/{medium}', [MediaController::class, 'update'])->name('media.update');
            Route::delete('media/{medium}', [MediaController::class, 'destroy'])->name('media.destroy');

            Route::get('menus', [MenuController::class, 'index'])->name('menus.index');
            Route::put('menus/{menu:id}', [MenuController::class, 'update'])->name('menus.update');

            Route::get('analytics', [AnalyticsController::class, 'index'])->name('analytics.index');
        });

        // Settings and users — admins only.
        Route::middleware('can:manage-settings')->group(function (): void {
            Route::get('settings', [SettingController::class, 'edit'])->name('settings.edit');
            Route::put('settings', [SettingController::class, 'update'])->name('settings.update');

            Route::get('backups', [BackupController::class, 'index'])->name('backups.index');
            Route::post('backups', [BackupController::class, 'store'])->name('backups.store');
            Route::get('backups/{name}/download', [BackupController::class, 'download'])
                ->where('name', 'backup-[0-9_-]+\.zip')
                ->name('backups.download');
            Route::delete('backups/{name}', [BackupController::class, 'destroy'])
                ->where('name', 'backup-[0-9_-]+\.zip')
                ->name('backups.destroy');
        });

        Route::middleware('can:manage-users')->group(function (): void {
            Route::get('users', [UserController::class, 'index'])->name('users.index');
            Route::post('users', [UserController::class, 'store'])->name('users.store');
            Route::put('users/{user}', [UserController::class, 'update'])->name('users.update');
            Route::delete('users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
        });
    });
