<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Compiler\Compiler;

use Latte\CompileException;

interface CompilerInterface
{
    /**
     * @param string $templateContent latte content
     * @return string php content
     * @throws CompileException
     */
    public function compile(string $templateContent, ?string $actualClass, string $context = ''): string;

    /**
     * @return array<string, string|array{string, string}|array{object, string}|callable>
     */
    public function getFilters(): array;

    /**
     * @return array<string, string|array{string, string}|array{object, string}|callable>
     */
    public function getFunctions(): array;
}
