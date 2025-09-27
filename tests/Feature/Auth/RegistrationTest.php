<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Http\Controllers\Auth\RegisteredUserController;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

#[CoversClass(RegisteredUserController::class)]
final class RegistrationTest extends TestCase
{
    use RefreshDatabase;

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
        $user = User::where('email', 'test@example.com')->first();
        $this->assertNotNull($user);
        $this->assertEquals('Test', $user->first_name);
        $this->assertEquals('User', $user->last_name);

        // Verify Registered event was dispatched
        Event::assertDispatched(Registered::class, fn ($event): bool => $event->user->id === $user->id);
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
