<?php

namespace Tests\Feature\Settings;

use App\Http\Controllers\Settings\PasswordController;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Tests\AbstractTestCase;

#[CoversClass(PasswordController::class)]
class PasswordUpdateTest extends AbstractTestCase
{
    use RefreshDatabase;

    #[Test]
    public function password_update_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('password.edit'));

        $response->assertStatus(200);
    }

    #[Test]
    public function password_can_be_updated(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->from(route('password.edit'))->put(route('password.update'), [
            // @mago-expect lint:no-literal-password
            'current_password' => 'password',
            // @mago-expect lint:no-literal-password
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ]);

        $response->assertSessionHasNoErrors()->assertRedirect(route('password.edit'));

        static::assertTrue(Hash::check('new-password', $user->refresh()->password));
    }

    #[Test]
    public function correct_password_must_be_provided_to_update_password(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->from(route('password.edit'))->put(route('password.update'), [
            // @mago-expect lint:no-literal-password
            'current_password' => 'wrong-password',
            // @mago-expect lint:no-literal-password
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ]);

        $response->assertSessionHasErrors('current_password')->assertRedirect(route('password.edit'));
    }
}
