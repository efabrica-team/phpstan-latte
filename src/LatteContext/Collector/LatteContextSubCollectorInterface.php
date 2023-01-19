<?php

namespace Efabrica\PHPStanLatte\LatteContext\Collector;

use PhpParser\Node;
use PHPStan\Analyser\Scope;

/**
 * @template T
 */
interface LatteContextSubCollectorInterface
{
    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array;

    /**
     * @phpstan-return null|T[]
     */
    public function collect(Node $node, Scope $scope): ?array;

    public function isSupported(Node $node): bool;
}
