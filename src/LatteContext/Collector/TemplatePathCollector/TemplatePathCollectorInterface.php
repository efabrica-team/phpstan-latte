<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\LatteContext\Collector\TemplatePathCollector;

use PhpParser\Node;
use PHPStan\Analyser\Scope;

interface TemplatePathCollectorInterface
{
    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array;

    /**
     * @return string[]|null
     */
    public function collect(Node $node, Scope $scope): ?array;
}
