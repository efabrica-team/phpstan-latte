<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\LatteContext\Collector;

use Efabrica\PHPStanLatte\LatteContext\CollectedData\CollectedError;
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
     * @phpstan-return null|array<T|CollectedError>
     */
    public function collect(Node $node, Scope $scope): ?array;

    public function isSupported(Node $node): bool;
}
