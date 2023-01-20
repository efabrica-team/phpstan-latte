<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Compiler\NodeVisitor\Behavior;

trait FiltersNodeVisitorBehavior
{
    /** @var array<string, string|array{string, string}|array{object, string}|callable> */
    private array $filters = [];

    /**
     * @param array<string, string|array{string, string}|array{object, string}|callable> $filters
     */
    public function setFilters(array $filters): void
    {
        $this->filters = $filters;
    }
}
