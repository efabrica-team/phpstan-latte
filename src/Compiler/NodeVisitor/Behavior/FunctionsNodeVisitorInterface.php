<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Compiler\NodeVisitor\Behavior;

interface FunctionsNodeVisitorInterface
{
    /**
     * @param array<string, string|array{string, string}|array{object, string}|callable> $functions
     */
    public function setFunctions(array $functions): void;
}
