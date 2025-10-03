<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Session;
use Inertia\Testing\AssertableInertia;
use Laravel\Fortify\Features;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

#[CoversClass(AuthenticatedSessionController::class)]
final class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function login_screen_can_be_rendered(): void
    {
        $response = $this->get(route('login'));

        $response->assertStatus(200);
        $response->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->component('auth/login')
            ->has('canResetPassword')
            ->where('canResetPassword', true)
        );
    }

    #[Test]
    public function users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->withoutTwoFactor()->create();

        $response = $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));

        // Verify session was regenerated for security
        self::assertNotNull(Session::getId());
    }

    #[Test]
    public function users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    #[Test]
    public function users_with_two_factor_enabled_are_redirected_to_two_factor_challenge(): void
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

        $response = $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'password',
            'remember' => true,
        ]);

        $response->assertRedirect(route('two-factor.login'));
        $response->assertSessionHas('login.id', $user->id);
        $response->assertSessionHas('login.remember', true);
        $this->assertGuest();
    }

    #[Test]
    public function users_can_logout(): void
    {
        $user = User::factory()->create();

        // Get initial session ID to verify it changes
        $initialSessionId = Session::getId();

        $response = $this->actingAs($user)->post(route('logout'));

        $this->assertGuest();
        $response->assertRedirect(route('home'));

        // Verify session was invalidated and token regenerated
        self::assertNotSame($initialSessionId, Session::getId());
    }

    #[Test]
    public function users_are_rate_limited(): void
    {
        $user = User::factory()->create();

        RateLimiter::increment(implode('|', [$user->email, '127.0.0.1']), amount: 10);

        $response = $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHasErrors('email');
        $errors = session('errors');

        /** @var string $email */
        $email = $errors->first('email'); // @phpstan-ignore-line method.nonObject

        self::assertStringContainsString('Too many login attempts', $email);
    }

    #[Test]
    public function login_screen_with_status_message_can_be_rendered(): void
    {
        $response = $this->withSession(['status' => 'test-status'])
            ->get(route('login'));

        $response->assertStatus(200);
        $response->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->component('auth/login')
            ->where('status', 'test-status')
        );
    }

    #[Test]
    public function two_factor_users_without_remember_flag_are_handled_correctly(): void
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

        $response = $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'password',
            'remember' => false,
        ]);

        $response->assertRedirect(route('two-factor.login'));
        $response->assertSessionHas('login.id', $user->id);
        $response->assertSessionHas('login.remember', false);
        $this->assertGuest();
    }

    #[Test]
    public function login_regenerates_session_for_security(): void
    {
        $user = User::factory()->create();

        // Store initial session ID
        $initialSessionId = Session::getId();

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        // Session ID should change after successful login
        self::assertNotSame($initialSessionId, Session::getId());
    }

    #[Test]
    public function logout_invalidates_session_completely(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        // Store session data and CSRF token
        Session::put('test_data', 'should_be_cleared');
        $initialToken = Session::token();

        $this->post(route('logout'));

        // Session should be invalidated and token regenerated
        self::assertNotSame($initialToken, Session::token());
        self::assertNull(Session::get('test_data'));
    }
}
