<?php

declare(strict_types=1);

namespace Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\AbstractTestCase;

final class ExampleTest extends AbstractTestCase
{
    use RefreshDatabase;

    #[Test]
    public function that_true_is_true(): void
    {
        self::assertTrue(true);
    }
}
