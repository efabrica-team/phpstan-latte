<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Compiler\NodeVisitor\Behavior;

interface FiltersNodeVisitorInterface
{
    /**
     * @param array<string, string|array{string, string}|array{object, string}|callable> $filters
     */
    public function setFilters(array $filters): void;
}
