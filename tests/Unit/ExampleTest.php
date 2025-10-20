<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class ExampleTest extends TestCase
{
    #[Test]
    public function that_true_is_true(): void
    {
        // @phpstan-ignore-next-line method.alreadyNarrowedType
        self::assertTrue(true);
    }
}
