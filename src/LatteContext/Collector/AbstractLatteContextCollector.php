<?php

namespace Efabrica\PHPStanLatte\LatteContext\Collector;

use Efabrica\PHPStanLatte\LatteContext\CollectedData\CollectedError;
use Efabrica\PHPStanLatte\LatteContext\CollectedData\CollectedLatteContextObject;
use Efabrica\PHPStanLatte\Resolver\NameResolver\NameResolver;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;

/**
 * @template T of CollectedLatteContextObject
 */
abstract class AbstractLatteContextCollector
{
    protected NameResolver $nameResolver;

    protected ReflectionProvider $reflectionProvider;

    public function __construct(
        NameResolver $nameResolver,
        ReflectionProvider $reflectionProvider
    ) {
        $this->nameResolver = $nameResolver;
        $this->reflectionProvider = $reflectionProvider;
    }

    /**
     * @return array<class-string<Node>>
     */
    abstract public function getNodeTypes(): array;

    /**
     * @param Node $node
     * @phpstan-return null|array<T|CollectedError>
     */
    abstract public function collectData(Node $node, Scope $scope): ?array;
}
