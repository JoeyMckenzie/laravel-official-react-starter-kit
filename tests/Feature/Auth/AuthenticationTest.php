<?php

namespace Tests\Feature\Auth;

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Tests\AbstractTestCase;

#[CoversClass(AuthenticatedSessionController::class)]
class AuthenticationTest extends AbstractTestCase
{
    use RefreshDatabase;

    #[Test]
    public function login_screen_can_be_rendered(): void
    {
        $response = $this->get(route('login'));

        $response->assertStatus(200);
    }

    #[Test]
    public function users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create();

        $response = $this->post(route('login.store'), [
            'email' => $user->email,
            // @mago-expect lint:no-literal-password
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    #[Test]
    public function users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $this->post(route('login.store'), [
            'email' => $user->email,
            // @mago-expect lint:no-literal-password
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    #[Test]
    public function users_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('logout'));

        $this->assertGuest();
        $response->assertRedirect(route('home'));
    }

    #[Test]
    public function users_are_rate_limited(): void
    {
        $user = User::factory()->create();

        for ($i = 0; $i < 5; $i++) {
            $this->post(route('login.store'), [
                'email' => $user->email,
                // @mago-expect lint:no-literal-password
                'password' => 'wrong-password',
            ])
                ->assertStatus(302)
                ->assertSessionHasErrors([
                    'email' => 'These credentials do not match our records.',
                ]);
        }

        $response = $this->post(route('login.store'), [
            'email' => $user->email,
            // @mago-expect lint:no-literal-password
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHasErrors('email');

        $errors = session('errors');

        static::assertStringContainsString('Too many login attempts', $errors->first('email'));
    }
}
