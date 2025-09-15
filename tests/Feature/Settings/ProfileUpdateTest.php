<?php

declare(strict_types=1);

namespace Tests\Feature\Settings;

use App\Http\Controllers\Settings\ProfileController;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Tests\AbstractTestCase;

#[CoversClass(ProfileController::class)]
final class ProfileUpdateTest extends AbstractTestCase
{
    use RefreshDatabase;

    #[Test]
    public function profile_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('profile.edit'));

        $response->assertOk();
    }

    #[Test]
    public function profile_information_can_be_updated(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->patch(route('profile.update'), [
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $response->assertSessionHasNoErrors()->assertRedirect(route('profile.edit'));

        $user->refresh();

        self::assertSame('Test User', $user->name);
        self::assertSame('test@example.com', $user->email);
        self::assertNull($user->email_verified_at);
    }

    #[Test]
    public function email_verification_status_is_unchanged_when_the_email_address_is_unchanged(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->patch(route('profile.update'), [
            'name' => 'Test User',
            'email' => $user->email,
        ]);

        $response->assertSessionHasNoErrors()->assertRedirect(route('profile.edit'));

        self::assertNotNull($user->refresh()->email_verified_at);
    }

    #[Test]
    public function user_can_delete_their_account(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->delete(route('profile.destroy'), [
            // @mago-expect lint:no-literal-password
            'password' => 'password',
        ]);

        $response->assertSessionHasNoErrors()->assertRedirect(route('home'));

        $this->assertGuest();
        self::assertNull($user->fresh());
    }

    #[Test]
    public function correct_password_must_be_provided_to_delete_account(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->from(route('profile.edit'))->delete(route('profile.destroy'), [
            // @mago-expect lint:no-literal-password
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHasErrors('password')->assertRedirect(route('profile.edit'));

        self::assertNotNull($user->fresh());
    }
}
