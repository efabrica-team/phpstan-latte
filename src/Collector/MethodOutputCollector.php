<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Collector;

use Efabrica\PHPStanLatte\Collector\ValueObject\CollectedMethodCall;
use Efabrica\PHPStanLatte\Resolver\CallResolver\CalledClassResolver;
use Efabrica\PHPStanLatte\Resolver\CallResolver\OutputCallResolver;
use Efabrica\PHPStanLatte\Resolver\NameResolver\NameResolver;
use PhpParser\Node;
use PHPStan\Analyser\Scope;

/**
 * @phpstan-import-type CollectedMethodCallArray from CollectedMethodCall
 * @extends AbstractCollector<Node, CollectedMethodCall, CollectedMethodCallArray>
 */
final class MethodOutputCollector extends AbstractCollector
{
    private NameResolver $nameResolver;

    private CalledClassResolver $calledClassResolver;

    private OutputCallResolver $outputCallResolver;

    public function __construct(NameResolver $nameResolver, CalledClassResolver $calledClassResolver, OutputCallResolver $outputCallResolver)
    {
        $this->nameResolver = $nameResolver;
        $this->calledClassResolver = $calledClassResolver;
        $this->outputCallResolver = $outputCallResolver;
    }

    public function getNodeType(): string
    {
        return Node::class;
    }

    /**
     * @param Node $node
     * @phpstan-return null|CollectedMethodCallArray[]
     */
    public function processNode(Node $node, Scope $scope): ?array
    {
        $classReflection = $scope->getClassReflection();
        if ($classReflection === null) {
            return null;
        }

        $functionName = $scope->getFunctionName();
        if ($functionName === null) {
            return null;
        }

        if (!$this->outputCallResolver->isOutputCallNode($node, $scope)) {
            return null;
        }

        $actualClassName = $classReflection->getName();
        $calledClassName = $this->calledClassResolver->resolve($node, $scope);
        $calledMethodName = $this->nameResolver->resolve($node);
        return $this->collectItem(new CollectedMethodCall(
            $actualClassName,
            $functionName,
            $calledClassName ?? '',
            $calledMethodName ?? '',
            CollectedMethodCall::TERMINATING_CALL
        ));
    }
}
