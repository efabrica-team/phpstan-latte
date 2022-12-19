<?php

namespace Efabrica\PHPStanLatte\Collector;

use Efabrica\PHPStanLatte\Collector\ValueObject\CollectedLatteContextObject;
use Efabrica\PHPStanLatte\Resolver\NameResolver\NameResolver;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;

/**
 * @template N of Node
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
     * @return class-string<N>
     */
    abstract public function getNodeType(): string;

    /**
     * @param N $node
     * @phpstan-return null|T[]
     */
    abstract public function collectData(Node $node, Scope $scope): ?array;
}
