<?php

namespace App\Providers;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Intervention\Image\ImageManager;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Image driver for responsive derivatives. GD encodes WebP quickly and
        // is always available; Imagick was markedly slower here for the same
        // work. Either produces AVIF only where the underlying library
        // (libavif / libheif) is present, so the service degrades to WebP-only
        // when it isn't. Override the binding to Imagick if a deployment needs
        // its format coverage.
        $this->app->singleton(
            ImageManager::class,
            fn () => ImageManager::gd(),
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Generate one CSP nonce per request and hand it to Vite, which stamps
        // it on every script tag it injects. The SecurityHeaders middleware
        // reads it back to build a nonce-based script-src (no 'unsafe-inline'),
        // and the Blade inline scripts reference it via Vite::cspNonce().
        // Set here, before any view renders, so even error pages carry it.
        Vite::useCspNonce();

        $this->configureDefaults();
        $this->configureAuthorization();
    }

    /**
     * CMS abilities. Editors manage content; only admins reach settings and
     * user management. Checked by the `can:` middleware on the CMS routes.
     */
    protected function configureAuthorization(): void
    {
        Gate::define('manage-content', fn (User $user) => $user->canManageContent());
        Gate::define('manage-settings', fn (User $user) => $user->isAdmin());
        Gate::define('manage-users', fn (User $user) => $user->isAdmin());
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        // Strong password policy in every real deployment, staging included, so
        // staff accounts are never held to a weaker bar off production. Relaxed
        // only for local development and the test suite. The breach-database
        // check needs network and slows tests, so it stays production-only.
        Password::defaults(function (): ?Password {
            if (app()->environment('local', 'testing')) {
                return null;
            }

            $rule = Password::min(12)->mixedCase()->letters()->numbers()->symbols();

            return app()->isProduction() ? $rule->uncompromised() : $rule;
        });
    }
}
