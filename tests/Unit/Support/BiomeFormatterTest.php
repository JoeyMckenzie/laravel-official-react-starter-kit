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
        // Arrange
        $mockReturnProcess = Mockery::mock(PendingProcess::class, function (MockInterface $mock): void {
            $mock->shouldReceive('failed')
                ->once()
                ->andReturn(false);
        });

        Process::shouldReceive('run')
            ->once()
            ->with('npm run fmt')
            ->andReturn($mockReturnProcess);

        // Act & Assert
        $formatter = new BiomeFormatter();
        $formatter->format('some-file.ts');
    });

    it('throws an exception when process fails', function (): void {
        // Arrange
        $pendingProcess = Mockery::mock(PendingProcess::class);
        $pendingProcess->shouldReceive('failed')->once()->andReturn(true);
        $pendingProcess->shouldReceive('throw')->once();

        Process::shouldReceive('run')
            ->once()
            ->with('npm run fmt')
            ->andReturn($pendingProcess);

        // Act & Assert
        $formatter = new BiomeFormatter();
        $formatter->format('some-file.ts');
    });
});
