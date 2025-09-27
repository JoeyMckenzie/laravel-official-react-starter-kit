<?php

declare(strict_types=1);

namespace Tests\Feature\Settings;

use App\Http\Controllers\Settings\ProfileController;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

#[CoversClass(ProfileController::class)]
final class ProfileUpdateTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function profile_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get(route('profile.edit'));

        $response->assertOk();
        $response->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->component('settings/profile')
            ->has('mustVerifyEmail')
            ->where('mustVerifyEmail', true)
        );
    }

    #[Test]
    public function profile_information_can_be_updated(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch(route('profile.update'), [
                'first_name' => 'Test',
                'last_name' => 'User',
                'email' => 'test@example.com',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('profile.edit'));

        $user->refresh();

        self::assertSame('Test', $user->first_name);
        self::assertSame('User', $user->last_name);
        self::assertSame('Test User', $user->full_name);
        self::assertSame('TU', $user->initials);
        self::assertSame('test@example.com', $user->email);
        self::assertNull($user->email_verified_at);
    }

    #[Test]
    public function email_verification_status_is_unchanged_when_the_email_address_is_unchanged(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch(route('profile.update'), [
                'first_name' => 'Test',
                'last_name' => 'User',
                'email' => $user->email,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('profile.edit'));

        self::assertNotNull($user->refresh()->email_verified_at);
    }

    #[Test]
    public function user_can_delete_their_account(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->delete(route('profile.destroy'), [
                'password' => 'password',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('home'));

        $this->assertGuest();
        self::assertNull($user->fresh());
    }

    #[Test]
    public function correct_password_must_be_provided_to_delete_account(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from(route('profile.edit'))
            ->delete(route('profile.destroy'), [
                'password' => 'wrong-password',
            ]);

        $response
            ->assertSessionHasErrors('password')
            ->assertRedirect(route('profile.edit'));

        self::assertNotNull($user->fresh());
    }

    #[Test]
    public function profile_page_displays_status_message(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->withSession(['status' => 'profile-updated'])
            ->get(route('profile.edit'));

        $response->assertOk();
        $response->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->component('settings/profile')
            ->where('status', 'profile-updated')
        );
    }

    #[Test]
    public function unauthenticated_user_cannot_access_profile_page(): void
    {
        $response = $this->get(route('profile.edit'));

        $response->assertRedirect('/login');
    }

    #[Test]
    public function profile_update_requires_all_fields(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch(route('profile.update'), []);

        $response->assertSessionHasErrors([
            'first_name',
            'last_name',
            'email',
        ]);
    }
}
