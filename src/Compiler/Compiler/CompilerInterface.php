<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Compiler\Compiler;

interface CompilerInterface
{
    /**
     * @param string $templateContent latte content
     * @return string php content
     */
    public function compile(string $templateContent): string;

    /**
     * @return array<string, string|array{string, string}>
     */
    public function getFilters(): array;
}
