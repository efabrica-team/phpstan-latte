<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Temp;

use RuntimeException;

final class TempDirResolver
{
    private string $tmpDir;

    public function __construct(?string $tmpDir)
    {
        $tmpDir = $tmpDir ? rtrim($tmpDir, DIRECTORY_SEPARATOR) : sys_get_temp_dir() . '/phpstan-latte';
        if (!is_dir($tmpDir)) {
            if (!@mkdir($tmpDir) && !is_dir($tmpDir)) {
                throw new RuntimeException(sprintf('Cannot create temp dir "%s"', $tmpDir));
            }
        }
        $tmpDir = realpath($tmpDir) ?: $tmpDir;
        if (!is_writable($tmpDir)) {
            throw new RuntimeException(sprintf('Temp dir "%s" is not writable', $tmpDir));
        }
        $this->tmpDir = $tmpDir;
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
