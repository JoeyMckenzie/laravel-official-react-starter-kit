<?php

declare(strict_types=1);

namespace Tests\Unit\Support;

use App\Support\BiomeFormatter;
use Illuminate\Process\PendingProcess;
use Illuminate\Support\Facades\Process;
use Mockery;
use Mockery\MockInterface;

covers(BiomeFormatter::class);

describe(BiomeFormatter::class, function (): void {
    it('formats using npm run fmt', function (): void {

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
    });

    it('throws an exception when process fails', function (): void {
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
    });
});
