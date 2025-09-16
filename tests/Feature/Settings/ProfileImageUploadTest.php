<?php

declare(strict_types=1);

namespace Tests\Feature\Settings;

use App\Http\Controllers\Settings\ProfileImageController;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

#[CoversClass(ProfileImageController::class)]
final class ProfileImageUploadTest extends TestCase
{
    use RefreshDatabase;

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
        $response->assertSuccessful();
        $response->assertJson([
            'message' => 'Profile image uploaded successfully',
        ]);

        $user->refresh();
        self::assertNotNull($user->profile_image);
        self::assertStringContainsString($user->id.'_', $user->profile_image);
        self::assertNotNull($user->profile_image_url);
        Storage::disk('public')->assertExists("profile-images/{$user->profile_image}");
    }

    #[Test]
    public function profile_image_upload_replaces_existing_image(): void
    {
        Storage::fake('public');
        $user = User::factory()->create(['profile_image' => 'old_image.jpg']);

        // Create the old file to simulate existing image
        Storage::disk('public')->put('profile-images/old_image.jpg', 'fake content');

        $file = UploadedFile::fake()->image('new_profile.jpg', 200, 200);

        $response = $this
            ->actingAs($user)
            ->post(route('profile.image.store'), [
                'image' => $file,
            ]);

        $response->assertSuccessful();

        $user->refresh();
        self::assertNotSame('old_image.jpg', $user->profile_image);
        Storage::disk('public')->assertMissing('profile-images/old_image.jpg');
        Storage::disk('public')->assertExists("profile-images/{$user->profile_image}");
    }

    #[Test]
    public function user_can_delete_profile_image(): void
    {
        Storage::fake('public');
        $user = User::factory()->create(['profile_image' => 'test_image.jpg']);

        // Create the file to simulate existing image
        Storage::disk('public')->put('profile-images/test_image.jpg', 'fake content');

        $response = $this
            ->actingAs($user)
            ->delete(route('profile.image.destroy'));

        $response->assertSuccessful();
        $response->assertJson([
            'message' => 'Profile image deleted successfully',
        ]);

        $user->refresh();
        self::assertNull($user->profile_image);
        self::assertNull($user->profile_image_url);
        Storage::disk('public')->assertMissing('profile-images/test_image.jpg');
    }

    #[Test]
    public function deleting_non_existent_profile_image_succeeds(): void
    {
        $user = User::factory()->create(['profile_image' => null]);

        $response = $this
            ->actingAs($user)
            ->delete(route('profile.image.destroy'));

        $response->assertSuccessful();
        $response->assertJson([
            'message' => 'Profile image deleted successfully',
        ]);
    }

    #[Test]
    public function profile_image_upload_requires_authentication(): void
    {
        $file = UploadedFile::fake()->image('profile.jpg', 200, 200);

        $response = $this->post(route('profile.image.store'), [
            'image' => $file,
        ]);

        $response->assertRedirect('/login');
    }

    #[Test]
    public function profile_image_delete_requires_authentication(): void
    {
        $response = $this->delete(route('profile.image.destroy'));

        $response->assertRedirect('/login');
    }

    #[Test]
    public function profile_image_upload_validates_file_type(): void
    {
        $user = User::factory()->create();
        $file = UploadedFile::fake()->create('document.pdf', 100);

        $response = $this
            ->actingAs($user)
            ->post(route('profile.image.store'), [
                'image' => $file,
            ]);

        $response->assertSessionHasErrors('image');
    }

    #[Test]
    public function profile_image_upload_validates_file_size(): void
    {
        $user = User::factory()->create();
        $file = UploadedFile::fake()->image('large.jpg', 100, 100)->size(6000); // 6MB

        $response = $this
            ->actingAs($user)
            ->post(route('profile.image.store'), [
                'image' => $file,
            ]);

        $response->assertSessionHasErrors('image');
    }

    #[Test]
    public function profile_image_upload_validates_minimum_dimensions(): void
    {
        $user = User::factory()->create();
        $file = UploadedFile::fake()->image('small.jpg', 50, 50); // Too small

        $response = $this
            ->actingAs($user)
            ->post(route('profile.image.store'), [
                'image' => $file,
            ]);

        $response->assertSessionHasErrors('image');
    }

    #[Test]
    public function profile_image_upload_validates_maximum_dimensions(): void
    {
        $user = User::factory()->create();
        $file = UploadedFile::fake()->image('huge.jpg', 3000, 3000); // Too large

        $response = $this
            ->actingAs($user)
            ->post(route('profile.image.store'), [
                'image' => $file,
            ]);

        $response->assertSessionHasErrors('image');
    }

    #[Test]
    public function profile_image_upload_requires_image_file(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->post(route('profile.image.store'), []);

        $response->assertSessionHasErrors('image');
    }
}
