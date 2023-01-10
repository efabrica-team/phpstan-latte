<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\LatteContext\Collector\TemplatePathCollector;

use PhpParser\Node;
use PHPStan\Analyser\Scope;

interface TemplatePathCollectorInterface
{
    public function isSupported(Node $node): bool;

    /**
     * @return string[]|null
     */
    public function collect(Node $node, Scope $scope): ?array;
}
