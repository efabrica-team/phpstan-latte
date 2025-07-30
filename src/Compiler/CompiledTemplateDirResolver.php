<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Compiler;

final class CompiledTemplateDirResolver
{
    private string $tmpDir;

    public function __construct(?string $tmpDir)
    {
        $baseTmpDir = $tmpDir ? rtrim($tmpDir, '/') : sys_get_temp_dir() . '/phpstan-latte/';
        $this->tmpDir = $baseTmpDir . '/compiled-templates/';
    }

    public function resolve(): string
    {
        return $this->tmpDir;
    }
}
