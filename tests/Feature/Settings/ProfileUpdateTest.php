<?php

declare(strict_types=1);

namespace Tests\Feature\Settings;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

describe('Profile update', function (): void {
    it('can display the profile page', function (): void {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get('/settings/profile');

        $response->assertOk();
    });

    it('can update profile information', function (): void {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/settings/profile', [
                'first_name' => 'Test',
                'last_name' => 'User',
                'email' => 'test@example.com',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/settings/profile');

        $user->refresh();

        $this->assertSame('Test User', $user->full_name);
        $this->assertSame('test@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
    });

    it('preserves email verification status when email is unchanged', function (): void {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/settings/profile', [
                'first_name' => 'Test',
                'last_name' => 'User',
                'email' => $user->email,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/settings/profile');

        $this->assertNotNull($user->refresh()->email_verified_at);
    });

    it('allows users to delete their account', function (): void {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->delete('/settings/profile', [
                'password' => 'password',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/');

        $this->assertGuest();
        $this->assertNull($user->fresh());
    });

    it('requires correct password to delete account', function (): void {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from('/settings/profile')
            ->delete('/settings/profile', [
                'password' => 'wrong-password',
            ]);

        $response
            ->assertSessionHasErrors('password')
            ->assertRedirect('/settings/profile');

        $this->assertNotNull($user->fresh());
    });

    it('ensures profile photo can be uploaded', function (): void {
        $user = User::factory()->create();

        Storage::fake('public');

        $response = $this
            ->actingAs($user)
            ->patch('/settings/profile', [
                'first_name' => 'Test',
                'last_name' => 'User',
                'email' => $user->email,
                'profile_image' => UploadedFile::fake()->image('photo.jpg'),
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/settings/profile');

        $user->refresh();

        expect($user->avatar)->not->toBeNull();
        expect(Storage::disk('public')->exists($user->avatar))->toBeTrue();
    });

    it('ensures profile photo can be removed', function (): void {
        $user = User::factory()->create();

        Storage::fake('public');

        $response = $this->actingAs($user)->patch('/settings/profile', [
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => $user->email,
            'profile_image' => UploadedFile::fake()->image('photo.jpg'),
        ]);

        $response->assertSessionHasNoErrors()
            ->assertRedirect('/settings/profile');

        $user->refresh();
        $this->assertNotNull($user->avatar);
        $this->assertTrue(Storage::disk('public')->exists($user->avatar));

        $oldPath = $user->avatar;

        $response = $this->actingAs($user)->delete('/settings/profile-photo');

        $response->assertSessionHasNoErrors()
            ->assertRedirect('/settings/profile');

        $user->refresh();
        $this->assertNull($user->avatar);
        $this->assertFalse(Storage::disk('public')->exists($oldPath));
    });
});
