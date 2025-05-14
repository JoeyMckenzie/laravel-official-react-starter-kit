<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

describe('Registration', function (): void {
    it('can render the registration screen', function (): void {
        $response = $this->get('/register');

        $response->assertStatus(200);
    });

    it('allows new users to register', function (): void {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    });
});
