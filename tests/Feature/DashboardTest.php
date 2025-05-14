<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;

describe('Dashboard', function (): void {
    it('redirects guests to the login page', function (): void {
        $response = $this->get('/dashboard');
        $response->assertRedirect('/login');
    });

    it('allows authenticated users to visit the dashboard', function (): void {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get('/dashboard');
        $response->assertStatus(200);
    });
});
