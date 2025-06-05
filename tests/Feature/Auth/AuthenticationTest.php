<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\User;

describe('Authentication', function (): void {
    it('can render the login screen', function (): void {
        $response = $this->get('/login');

        $response->assertStatus(200);
    });

    it('can authenticate users using the login screen', function (): void {
        $user = User::factory()->create();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    });

    it('cannot authenticate users with invalid password', function (): void {
        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    });

    it('throttles login attempts after too many failed attempts', function (): void {
        $user = User::factory()->create();

        // Attempt to login with wrong password multiple times to trigger rate limiting
        collect(range(1, 5))
            ->each(fn () => $this->post('/login', [
                'email' => $user->email,
                'password' => 'wrong-password',
            ]));

        // The next attempt should be throttled
        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertStringContainsString(
            'Too many login attempts',
            collect($response->exception->errors())->flatten()->first()
        );
    });

    it('allows users to logout', function (): void {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/');
    });
});
