<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class PasswordConfirmationTest extends TestCase
{
    #[Test]
    public function confirm_password_screen_can_be_rendered(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get(route('password.confirm'));

        $response->assertStatus(200);

        $response->assertInertia(fn (Assert $page): Assert => $page
            ->component('auth/confirm-password')
        );
    }

    #[Test]
    public function password_can_be_confirmed(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->post(route('password.confirm.store'), [
                'password' => 'password',
            ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();
    }

    #[Test]
    public function password_is_not_confirmed_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->post(route('password.confirm.store'), [
                'password' => 'wrong-password',
            ]);

        $response->assertSessionHasErrors();
    }

    #[Test]
    public function password_confirmation_requires_authentication(): void
    {
        $response = $this->get(route('password.confirm'));

        $response->assertRedirect(route('login'));
    }
}
