<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\LatteContext\Collector\VariableCollector;

use Efabrica\PHPStanLatte\LatteContext\CollectedData\CollectedVariable;
use PhpParser\Node;
use PHPStan\Analyser\Scope;

interface VariableCollectorInterface
{
    public function isSupported(Node $node): bool;

    /**
     * @return CollectedVariable[]
     */
    public function collect(Node $node, Scope $scope): array;
}
