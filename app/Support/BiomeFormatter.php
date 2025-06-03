<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Facades\Process;
use Spatie\TypeScriptTransformer\Formatters\Formatter;

final readonly class BiomeFormatter implements Formatter
{
    public function format(string $file): void
    {
        $process = Process::run('npm run fmt');

        if ($process->failed()) {
            $process->throw();
        }
    }
}
