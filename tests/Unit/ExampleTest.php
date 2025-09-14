<?php

namespace Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\AbstractTestCase;

class ExampleTest extends AbstractTestCase
{
    use RefreshDatabase;

    #[Test]
    public function that_true_is_true(): void
    {
        static::assertTrue(true);
    }
}
