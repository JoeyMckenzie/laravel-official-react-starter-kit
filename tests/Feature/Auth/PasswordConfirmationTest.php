<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Http\Controllers\Auth\ConfirmablePasswordController;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

#[CoversClass(ConfirmablePasswordController::class)]
final class PasswordConfirmationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function confirm_password_screen_can_be_rendered(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get(route('password.confirm'));

        $response->assertStatus(200);
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
}
