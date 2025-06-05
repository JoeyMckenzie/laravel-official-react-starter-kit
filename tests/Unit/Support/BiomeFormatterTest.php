<?php

declare(strict_types=1);

namespace Tests\Unit\Support;

use App\Support\BiomeFormatter;
use Illuminate\Process\PendingProcess;
use Illuminate\Support\Facades\Process;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class BiomeFormatterTest extends TestCase
{
    #[Test]
    public function it_formats_using_npm_run_fmt(): void
    {
        // Mock the Process facade
        Process::shouldReceive('run')
            ->once()
            ->with('npm run fmt')
            ->andReturn(
                Mockery::mock(PendingProcess::class, function (MockInterface $mock): void {
                    $mock->shouldReceive('failed')->once()->andReturn(false);
                })
            );

        // Create an instance of BiomeFormatter and call format
        $formatter = new BiomeFormatter();
        $formatter->format('some-file.ts');

        // No assertion needed as we're verifying the mock expectations
    }

    #[Test]
    public function it_throws_exception_when_process_fails(): void
    {
        // Mock the Process facade
        $pendingProcess = Mockery::mock(PendingProcess::class);
        $pendingProcess->shouldReceive('failed')->once()->andReturn(true);
        $pendingProcess->shouldReceive('throw')->once();

        Process::shouldReceive('run')
            ->once()
            ->with('npm run fmt')
            ->andReturn($pendingProcess);

        // Create an instance of BiomeFormatter and call format
        $formatter = new BiomeFormatter();
        $formatter->format('some-file.ts');

        // No assertion needed as we're verifying the mock expectations
    }
}
