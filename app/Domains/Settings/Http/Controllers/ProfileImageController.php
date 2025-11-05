<?php

declare(strict_types=1);

namespace App\Domains\Settings\Http\Controllers;

use App\Domains\Auth\Models\User;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\Filesystem\Factory as Filesystem;
use Illuminate\Contracts\Filesystem\Filesystem as Storage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

final class ProfileImageController extends Controller
{
    private readonly Storage $storage;

    public function __construct(
        Filesystem $filesystem,
    ) {
        $this->storage = $filesystem->disk('public');
    }

    /**
     * Upload a new profile image for the authenticated user.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'image' => [
                'required',
                'image',
                'max:5120', // 5MB in KB
                'dimensions:min_width=100,min_height=100,max_width=2000,max_height=2000',
            ],
        ]);

        /** @var User $user */
        $user = $request->user();

        if ($user->avatar !== null) {
            $this->storage->delete($user->avatar);
        }

        /** @var UploadedFile $file */
        $file = $request->file('image');
        $avatar = $file->store('avatars', 'public');

        if (is_string($avatar)) {
            $user->update([
                'avatar' => $avatar,
            ]);
        }

        return to_route('profile.edit');
    }

    /**
     * Delete the user's profile image.
     */
    public function destroy(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($user->avatar !== null) {
            $this->storage->delete("avatars/$user->avatar");
            $user->update([
                'avatar' => null,
            ]);
        }

        return to_route('profile.edit');
    }
}
