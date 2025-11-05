<?php

declare(strict_types=1);

namespace App\Domains\Settings\Http\Controllers;

use App\Domains\Settings\Http\Requests\TwoFactorAuthenticationRequest;
use App\Http\Controllers\Controller;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Fortify\Features;

final class TwoFactorAuthenticationController extends Controller implements HasMiddleware
{
    /**
     * Get the middleware that should be assigned to the controller.
     */
    public static function middleware(): array
    {
        return Features::optionEnabled(Features::twoFactorAuthentication(), 'confirmPassword')
            ? [new Middleware('password.confirm', only: ['show'])]
            : [];
    }

    /**
     * Show the user's two-factor authentication settings page.
     */
    public function show(TwoFactorAuthenticationRequest $request): Response
    {
        $user = $request->user();

        abort_if($user === null, 403);

        $request->ensureStateIsValid();
        $twoFactorEnabled = $user->hasEnabledTwoFactorAuthentication();

        return Inertia::render('settings/two-factor', [
            'twoFactorEnabled' => $twoFactorEnabled,
            'requiresConfirmation' => Features::optionEnabled(Features::twoFactorAuthentication(), 'confirm'),
        ]);
    }
}
