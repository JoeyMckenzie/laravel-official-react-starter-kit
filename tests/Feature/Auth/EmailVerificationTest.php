<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class EmailVerificationTest extends TestCase
{
    #[Test]
    public function email_verification_screen_can_be_rendered(): void
    {
        $user = User::factory()->unverified()->create();

        $response = $this->actingAs($user)->get(route('verification.notice'));

        $response->assertStatus(200);
        $response->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->component('auth/verify-email')
        );
    }

    #[Test]
    public function email_can_be_verified(): void
    {
        $user = User::factory()->unverified()->create();

        Event::fake();

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            [
                'id' => $user->id,
                'hash' => sha1((string) $user->email),
            ]
        );

        $response = $this->actingAs($user)->get($verificationUrl);

        Event::assertDispatched(Verified::class);
        self::assertTrue($user->fresh()?->hasVerifiedEmail());
        $response->assertRedirect(route('dashboard', absolute: false).'?verified=1');
    }

    #[Test]
    public function email_is_not_verified_with_invalid_hash(): void
    {
        $user = User::factory()->unverified()->create();

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1('wrong-email')]
        );

        $this->actingAs($user)->get($verificationUrl);

        self::assertFalse($user->fresh()?->hasVerifiedEmail());
    }

    #[Test]
    public function email_is_not_verified_with_invalid_user_id(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            [
                'id' => 123,
                'hash' => sha1((string) $user->email),
            ]
        );

        $this->actingAs($user)->get($verificationUrl);

        self::assertFalse($user->fresh()?->hasVerifiedEmail());
    }

    #[Test]
    public function verified_user_is_redirected_to_dashboard_from_verification_prompt(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($user)->get(route('verification.notice'));

        $response->assertRedirect(route('dashboard', absolute: false));
    }

    #[Test]
    public function already_verified_user_visiting_verification_link_is_redirected_without_firing_event_again(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        Event::fake();

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            [
                'id' => $user->id,
                'hash' => sha1((string) $user->email),
            ]
        );

        $this->actingAs($user)
            ->get($verificationUrl)
            ->assertRedirect(route('dashboard', absolute: false).'?verified=1');

        self::assertTrue($user->fresh()?->hasVerifiedEmail());
        Event::assertNotDispatched(Verified::class);
    }

    #[Test]
    public function unauthenticated_user_cannot_access_verification_prompt(): void
    {
        $response = $this->get(route('verification.notice'));

        $response->assertStatus(302);
        $response->assertRedirect('/login');
    }

    #[Test]
    public function verification_screen_with_status_message_can_be_rendered(): void
    {
        $user = User::factory()->unverified()->create();

        $response = $this->actingAs($user)
            ->withSession(['status' => 'verification-link-sent'])
            ->get(route('verification.notice'));

        $response->assertStatus(200);
        $response->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->component('auth/verify-email')
            ->where('status', 'verification-link-sent')
        );
    }
}
