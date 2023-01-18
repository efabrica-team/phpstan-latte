<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\LatteContext\Collector\VariableCollector;

use Efabrica\PHPStanLatte\LatteContext\CollectedData\CollectedVariable;
use PhpParser\Node;
use PHPStan\Analyser\Scope;

interface VariableCollectorInterface
{
    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array;

    /**
     * @return CollectedVariable[]|null null = not a variables node
     */
    public function collect(Node $node, Scope $scope): ?array;
}
