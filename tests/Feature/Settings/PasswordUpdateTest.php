<?php

declare(strict_types=1);

namespace Tests\Feature\Settings;

use App\Http\Controllers\Settings\PasswordController;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

#[CoversClass(PasswordController::class)]
final class PasswordUpdateTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function password_update_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get(route('password.edit'));

        $response->assertStatus(200);
    }

    #[Test]
    public function password_can_be_updated(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from(route('password.edit'))
            ->put(route('password.update'), [
                'current_password' => 'password',
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('password.edit'));

        self::assertTrue(Hash::check('new-password', $user->refresh()->password));
    }

    #[Test]
    public function correct_password_must_be_provided_to_update_password(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from(route('password.edit'))
            ->put(route('password.update'), [
                'current_password' => 'wrong-password',
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ]);

        $response
            ->assertSessionHasErrors('current_password')
            ->assertRedirect(route('password.edit'));
    }

    #[Test]
    public function password_update_requires_current_password(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from(route('password.edit'))
            ->put(route('password.update'), [
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ]);

        $response
            ->assertSessionHasErrors('current_password')
            ->assertRedirect(route('password.edit'));
    }

    #[Test]
    public function password_update_requires_new_password(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from(route('password.edit'))
            ->put(route('password.update'), [
                'current_password' => 'password',
                'password_confirmation' => 'new-password',
            ]);

        $response
            ->assertSessionHasErrors('password')
            ->assertRedirect(route('password.edit'));
    }

    #[Test]
    public function password_update_requires_confirmation(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from(route('password.edit'))
            ->put(route('password.update'), [
                'current_password' => 'password',
                'password' => 'new-password',
                'password_confirmation' => 'different-password',
            ]);

        $response
            ->assertSessionHasErrors('password')
            ->assertRedirect(route('password.edit'));
    }

    #[Test]
    public function unauthenticated_user_cannot_update_password(): void
    {
        $response = $this->put(route('password.update'), [
            'current_password' => 'password',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ]);

        $response->assertRedirect('/login');
    }
}
