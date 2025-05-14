<?php

declare(strict_types=1);

namespace App\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
final class UserData extends Data
{
    public int $id;

    public string $firstName;

    public string $lastName;

    public string $fullName;

    public string $initials;

    public string $email;

    public ?string $profileImage = null;

    public ?string $emailVerifiedAt = null;

    public string $createdAt;

    public string $updatedAt;
}
