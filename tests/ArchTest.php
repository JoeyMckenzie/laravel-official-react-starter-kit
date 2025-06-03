<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Contracts\Validation\ValidationRule;

arch()->preset()->laravel();
arch()->preset()->php();
arch()->preset()->security();

arch('Types and equality are strict')
    ->expect('App\\')
    ->toUseStrictTypes()
    ->toUseStrictEquality();

arch('Rules should be suffixed with rule')
    ->expect('App\\Http\\Rules')
    ->classes()
    ->toBeFinal()
    ->toHaveSuffix('Rule')
    ->toImplement(ValidationRule::class);

arch('All test files are strictly typed')
    ->expect('Tests\\')
    ->toUseStrictTypes();

arch('All enums are string backed')
    ->expect('App\\Enums\\')
    ->toBeStringBackedEnums();

arch('All value objects are immutable')
    ->expect('App\\ValueObjects\\')
    ->toBeReadonly()
    ->and('App\\ValueObjects')
    ->toBeFinal();

arch('All contracts are interfaces')
    ->expect('App\\Contracts\\')
    ->toBeInterfaces();
