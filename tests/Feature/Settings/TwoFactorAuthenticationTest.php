<?php

declare(strict_types=1);

namespace Tests\Feature\Settings;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Fortify\Features;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class TwoFactorAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function two_factor_settings_page_can_be_rendered(): void
    {
        if (! Features::canManageTwoFactorAuthentication()) {
            self::markTestSkipped('Two-factor authentication is not enabled.');
        }

        Features::twoFactorAuthentication([
            'confirm' => true,
            'confirmPassword' => true,
        ]);

        $user = User::factory()
            ->withoutTwoFactor()
            ->create();

        $this->actingAs($user)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->get(route('two-factor.show'))
            ->assertInertia(fn (Assert $page): Assert => $page
                ->component('settings/two-factor')
                ->where('twoFactorEnabled', false)
            );
    }

    #[Test]
    public function two_factor_settings_page_requires_password_confirmation_when_enabled(): void
    {
        if (! Features::canManageTwoFactorAuthentication()) {
            self::markTestSkipped('Two-factor authentication is not enabled.');
        }

        $user = User::factory()->create();

        Features::twoFactorAuthentication([
            'confirm' => true,
            'confirmPassword' => true,
        ]);

        $response = $this->actingAs($user)
            ->get(route('two-factor.show'));

        $response->assertRedirect(route('password.confirm'));
    }

    #[Test]
    public function two_factor_settings_page_does_not_requires_password_confirmation_when_disabled(): void
    {
        if (! Features::canManageTwoFactorAuthentication()) {
            self::markTestSkipped('Two-factor authentication is not enabled.');
        }

        $user = User::factory()->create();

        Features::twoFactorAuthentication([
            'confirm' => true,
            'confirmPassword' => false,
        ]);

        $this->actingAs($user)
            ->get(route('two-factor.show'))
            ->assertOk()
            ->assertInertia(fn (Assert $page): Assert => $page
                ->component('settings/two-factor')
            );
    }

    #[Test]
    public function two_factor_settings_page_returns_forbidden_response_when_two_factor_is_disabled(): void
    {
        if (! Features::canManageTwoFactorAuthentication()) {
            self::markTestSkipped('Two-factor authentication is not enabled.');
        }

        Config::set('fortify.features', []);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->get(route('two-factor.show'))
            ->assertForbidden();
    }

    #[Test]
    public function users_can_enable_two_factor_authentication(): void
    {
        if (! Features::canManageTwoFactorAuthentication()) {
            self::markTestSkipped('Two-factor authentication is not enabled.');
        }

        Features::twoFactorAuthentication([
            'confirm' => true,
            'confirmPassword' => true,
        ]);

        $user = User::factory()
            ->withoutTwoFactor()
            ->create();

        $response = $this->actingAs($user)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->post(route('two-factor.enable'));

        $response->assertRedirect();

        $user->refresh();

        self::assertNotNull($user->two_factor_secret);
        self::assertNotNull($user->two_factor_recovery_codes);
        self::assertNull($user->two_factor_confirmed_at);
        self::assertFalse($user->hasEnabledTwoFactorAuthentication());
    }
}
