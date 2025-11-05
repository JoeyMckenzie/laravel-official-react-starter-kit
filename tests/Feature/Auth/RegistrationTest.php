<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Domains\Auth\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class RegistrationTest extends TestCase
{
    #[Test]
    public function registration_screen_can_be_rendered(): void
    {
        $response = $this->get(route('register'));

        $response->assertStatus(200);
    }

    #[Test]
    public function new_users_can_register(): void
    {
        Event::fake();

        $response = $this->post(route('register.store'), [
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));

        // Verify user was created with correct data
        /** @var User $user */
        $user = User::where('email', 'test@example.com')->first();
        self::assertNotNull($user);
        self::assertSame('Test', $user->first_name);
        self::assertSame('User', $user->last_name);

        // Verify Registered event was dispatched
        Event::assertDispatched(Registered::class, function (Registered $event) use ($user): bool {
            /** @var User $eventUser */
            $eventUser = $event->user;

            return $eventUser->id === $user->id;
        });
    }

    #[Test]
    public function registration_requires_all_fields(): void
    {
        $response = $this->post(route('register.store'), []);

        $response->assertSessionHasErrors([
            'first_name',
            'last_name',
            'email',
            'password',
        ]);
    }

    #[Test]
    public function registration_requires_password_confirmation(): void
    {
        $response = $this->post(route('register.store'), [
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'different-password',
        ]);

        $response->assertSessionHasErrors('password');
    }

    #[Test]
    public function registration_requires_unique_email(): void
    {
        User::factory()->create(['email' => 'test@example.com']);

        $response = $this->post(route('register.store'), [
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertSessionHasErrors('email');
    }
}
