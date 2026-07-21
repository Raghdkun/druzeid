<?php

use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\SecurityHeaders;
use App\Services\SiteChrome;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        $middleware->web(append: [
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
            SecurityHeaders::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // Render public HTTP errors inside the site shell so a 404/403/500
        // keeps the header, footer and navigation. The CMS/auth area and JSON
        // clients fall through to the framework defaults.
        $exceptions->respond(function (Response $response, Throwable $exception, Request $request) {
            $status = $response->getStatusCode();

            $isPublic = ! $request->is('cms', 'cms/*', 'settings', 'settings/*', 'dashboard', 'login', 'register', 'api/*');

            // Client errors (404/403/…) are not bugs, so brand them even in
            // debug mode; server errors defer to Ignition when debug is on.
            $serverError = in_array($status, [500, 503], true);
            $brandable = in_array($status, [403, 404, 419, 429, 500, 503], true);

            if ($isPublic
                && ! $request->expectsJson()
                && $brandable
                && ! ($serverError && app()->hasDebugModeEnabled())) {
                return inertia('site/error', [
                    'status' => $status,
                    ...app(SiteChrome::class)->props(),
                ])->toResponse($request)->setStatusCode($status);
            }

            return $response;
        });
    })->create();
