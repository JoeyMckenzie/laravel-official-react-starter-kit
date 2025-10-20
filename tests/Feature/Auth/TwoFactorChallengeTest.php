<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Fortify\Features;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class TwoFactorChallengeTest extends TestCase
{
    #[Test]
    public function two_factor_challenge_redirects_to_login_when_not_authenticated(): void
    {
        if (! Features::canManageTwoFactorAuthentication()) {
            self::markTestSkipped('Two-factor authentication is not enabled.');
        }

        $response = $this->get(route('two-factor.login'));

        $response->assertRedirect(route('login'));
    }

    #[Test]
    public function two_factor_challenge_can_be_rendered(): void
    {
        if (! Features::canManageTwoFactorAuthentication()) {
            self::markTestSkipped('Two-factor authentication is not enabled.');
        }

        Features::twoFactorAuthentication([
            'confirm' => true,
            'confirmPassword' => true,
        ]);

        $user = User::factory()->create();

        $user->forceFill([
            'two_factor_secret' => encrypt('test-secret'),
            'two_factor_recovery_codes' => encrypt(json_encode(['code1', 'code2'])),
            'two_factor_confirmed_at' => now(),
        ])->save();

        $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->get(route('two-factor.login'))
            ->assertOk()
            ->assertInertia(fn (Assert $page): Assert => $page
                ->component('auth/two-factor-challenge')
            );
    }
}
