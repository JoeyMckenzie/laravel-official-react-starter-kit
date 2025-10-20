<?php

declare(strict_types=1);

namespace Tests\Feature\Settings;

use App\Http\Controllers\Settings\ProfileImageController;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

#[CoversClass(ProfileImageController::class)]
final class ProfileImageUploadTest extends TestCase
{
    #[Test]
    public function user_can_upload_profile_image(): void
    {
        // Arrange
        Storage::fake('public');

        $user = User::factory()->create();
        $file = UploadedFile::fake()->image('profile.jpg', 200, 200);

        // Act
        $response = $this
            ->actingAs($user)
            ->post(route('profile.image.store'), [
                'image' => $file,
            ]);

        // Assert
        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('profile.edit'));

        $user->refresh();

        self::assertNotNull($user->profile_image);
        Storage::disk('public')->exists($user->profile_image);
    }

    #[Test]
    public function profile_image_upload_replaces_existing_image(): void
    {
        // Arrange
        Storage::fake('public');
        Storage::disk('public')->put('avatars/old_image.jpg', 'fake content');

        $user = User::factory()->create([
            'avatar' => 'old_image.jpg',
        ]);

        self::assertNotNull($user->avatar);
        self::assertSame('old_image.jpg', $user->avatar);

        $file = UploadedFile::fake()->image('new_profile.jpg', 200, 200);

        // Act
        $response = $this
            ->actingAs($user)
            ->post(route('profile.image.store'), [
                'image' => $file,
            ]);

        // Assert
        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('profile.edit'));

        $user->refresh();

        self::assertNotSame('old_image.jpg', $user->profile_image);
        Storage::disk('public')->missing('avatars/old_image.jpg');
        Storage::disk('public')->exists($user->profile_image ?? '');
    }

    #[Test]
    public function user_can_delete_profile_image(): void
    {
        // Arrange
        $user = User::factory()->create([
            'avatar' => 'test_image.jpg',
        ]);

        Storage::disk('public')->put('avatars/test_image.jpg', 'fake content');

        self::assertNotNull($user->profile_image);
        self::assertStringContainsString('test_image.jpg', $user->profile_image);

        $response = $this
            ->actingAs($user)
            ->delete(route('profile.image.destroy'));

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('profile.edit'));

        $user->refresh();

        self::assertNull($user->profile_image);
        Storage::disk('public')->missing('avatars/test_image.jpg');
    }

    #[Test]
    public function deleting_non_existent_profile_image_succeeds(): void
    {
        // Arrange
        $user = User::factory()->create([
            'avatar' => null,
        ]);

        // Act
        $response = $this
            ->actingAs($user)
            ->delete(route('profile.image.destroy'));

        // Assert
        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('profile.edit'));
    }

    #[Test]
    public function profile_image_upload_requires_authentication(): void
    {
        // Arrange
        $file = UploadedFile::fake()->image('profile.jpg', 200, 200);

        // Act
        $response = $this->post(route('profile.image.store'), [
            'image' => $file,
        ]);

        // assert
        $response->assertRedirect('/login');
    }

    #[Test]
    public function profile_image_delete_requires_authentication(): void
    {
        // Arrange & Act
        $response = $this->delete(route('profile.image.destroy'));

        // Assert
        $response->assertRedirect('/login');
    }

    #[Test]
    public function profile_image_upload_validates_file_type(): void
    {
        // Arrange
        $user = User::factory()->create();
        $file = UploadedFile::fake()->create('document.pdf', 100);

        // Act
        $response = $this
            ->actingAs($user)
            ->post(route('profile.image.store'), [
                'image' => $file,
            ]);

        // Assert
        $response->assertSessionHasErrors('image');
    }

    #[Test]
    public function profile_image_upload_validates_file_size(): void
    {
        // Arrange
        $user = User::factory()->create();
        $file = UploadedFile::fake()->image('large.jpg', 100, 100)->size(6000); // 6MB

        // Act
        $response = $this
            ->actingAs($user)
            ->post(route('profile.image.store'), [
                'image' => $file,
            ]);

        // Assert
        $response->assertSessionHasErrors('image');
    }

    #[Test]
    public function profile_image_upload_validates_minimum_dimensions(): void
    {
        // Arrange
        $user = User::factory()->create();
        $file = UploadedFile::fake()->image('small.jpg', 50, 50); // Too small

        // Act
        $response = $this
            ->actingAs($user)
            ->post(route('profile.image.store'), [
                'image' => $file,
            ]);

        // Assert
        $response->assertSessionHasErrors('image');
    }

    #[Test]
    public function profile_image_upload_validates_maximum_dimensions(): void
    {
        // Arrange
        $user = User::factory()->create();
        $file = UploadedFile::fake()->image('huge.jpg', 3000, 3000); // Too large

        // Act
        $response = $this
            ->actingAs($user)
            ->post(route('profile.image.store'), [
                'image' => $file,
            ]);

        // Assert
        $response->assertSessionHasErrors('image');
    }

    #[Test]
    public function profile_image_upload_requires_image_file(): void
    {
        // Arrange
        $user = User::factory()->create();

        // Act
        $response = $this
            ->actingAs($user)
            ->post(route('profile.image.store'), []);

        // Assert
        $response->assertSessionHasErrors('image');
    }
}
