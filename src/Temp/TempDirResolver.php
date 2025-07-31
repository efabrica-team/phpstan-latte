<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Temp;

final class TempDirResolver
{
    private string $tmpDir;

    public function __construct(?string $tmpDir)
    {
        $this->tmpDir = $tmpDir ? rtrim($tmpDir, DIRECTORY_SEPARATOR) : sys_get_temp_dir() . '/phpstan-latte';
    }

    public function resolveCompileDir(): string
    {
        return $this->tmpDir . DIRECTORY_SEPARATOR . 'compiled-templates' . DIRECTORY_SEPARATOR;
    }

    public function resolveAnalyseDir(): string
    {
        return $this->tmpDir . DIRECTORY_SEPARATOR . 'analysed-templates' . DIRECTORY_SEPARATOR;
    }

    public function resolveCollectorDir(): string
    {
        return $this->tmpDir . DIRECTORY_SEPARATOR . 'collector-cache' . DIRECTORY_SEPARATOR;
    }
}
