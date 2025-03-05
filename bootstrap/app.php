<?php

use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);

        // By default, shadcn/ui will set a sidebar_state cookie that we need our middleware to encrypt
        // so we can read it server side to allow for the proper state of it in between page refreshes
        $middleware->encryptCookies([
            'sidebar_state',
            'appearance',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
