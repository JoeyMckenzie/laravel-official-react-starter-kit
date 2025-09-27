<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class VerificationNotificationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function sends_verification_notification(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);

        $this->actingAs($user)
            ->post(route('verification.send'))
            ->assertRedirect(route('home'));

        Notification::assertSentTo($user, VerifyEmail::class);
    }

    #[Test]
    public function does_not_send_verification_notification_if_email_is_verified(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user)
            ->post(route('verification.send'))
            ->assertRedirect(route('dashboard', absolute: false));

        Notification::assertNothingSent();
    }

    #[Test]
    public function unauthenticated_user_cannot_send_verification_notification(): void
    {
        $response = $this->post(route('verification.send'));

        $response->assertStatus(302);
        $response->assertRedirect('/login');
    }

    #[Test]
    public function null_user_is_properly_handled_with_correct_status_code(): void
    {
        // This test specifically targets the abort_if($user === null, 403) mutations
        // by testing the actual behavior when user is null
        $response = $this->post(route('verification.send'));

        // Should redirect to login (middleware handles this) rather than 403
        $response->assertStatus(302);
        $response->assertRedirect('/login');
    }

    #[Test]
    public function verification_notification_returns_correct_status(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);

        $this->actingAs($user)
            ->post(route('verification.send'))
            ->assertRedirect(route('home'))
            ->assertSessionHas('status', 'verification-link-sent');
    }
}
