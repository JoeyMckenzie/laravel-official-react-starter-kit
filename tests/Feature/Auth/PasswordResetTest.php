<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

#[CoversClass(PasswordResetLinkController::class)]
final class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function reset_password_link_screen_can_be_rendered(): void
    {
        $response = $this->get(route('password.request'));

        $response->assertStatus(200);
        $response->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->component('auth/forgot-password')
        );
    }

    #[Test]
    public function reset_password_link_can_be_requested(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post(route('password.email'), ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class);
    }

    #[Test]
    public function reset_password_screen_can_be_rendered(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post(route('password.email'), ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) {
            $response = $this->get(route('password.reset', $notification->token));
            $response->assertStatus(200);

            return true;
        });
    }

    #[Test]
    public function password_can_be_reset_with_valid_token(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post(route('password.email'), ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use ($user) {
            $response = $this->post(route('password.store'), [
                'token' => $notification->token,
                'email' => $user->email,
                'password' => 'password',
                'password_confirmation' => 'password',
            ]);

            $response
                ->assertSessionHasNoErrors()
                ->assertRedirect(route('login'));

            return true;
        });
    }

    #[Test]
    public function password_cannot_be_reset_with_invalid_token(): void
    {
        $user = User::factory()->create();

        $response = $this->post(route('password.store'), [
            'token' => 'invalid-token',
            'email' => $user->email,
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertSessionHasErrors('email');
    }

    #[Test]
    public function password_reset_screen_with_status_message_can_be_rendered(): void
    {
        $response = $this->withSession(['status' => 'passwords.sent'])
            ->get(route('password.request'));

        $response->assertStatus(200);
        $response->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->component('auth/forgot-password')
            ->where('status', 'passwords.sent')
        );
    }

    #[Test]
    public function password_reset_request_validates_email_field(): void
    {
        $response = $this->post(route('password.email'), []);

        $response->assertSessionHasErrors('email');
    }

    #[Test]
    public function password_reset_request_for_nonexistent_email_still_returns_success_message(): void
    {
        $response = $this->post(route('password.email'), [
            'email' => 'nonexistent@example.com',
        ]);

        $response->assertRedirect()
            ->assertSessionHas('status', __('A reset link will be sent if the account exists.'));
    }
}
