<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\LatteContext\Collector;

use Efabrica\PHPStanLatte\LatteContext\CollectedData\CollectedMethod;
use PhpParser\Node;
use PhpParser\Node\Stmt\Return_;
use PhpParser\Node\Stmt\Throw_;
use PHPStan\Analyser\Scope;
use PHPStan\Node\ClassMethod;
use PHPStan\Node\ExecutionEndNode;

/**
 * @extends AbstractLatteContextCollector<CollectedMethod>
 */
final class MethodTerminatingCollector extends AbstractLatteContextCollector
{
    public function getNodeTypes(): array
    {
        return [ExecutionEndNode::class];
    }

    /**
     * @param ExecutionEndNode $node
     * @phpstan-return null|CollectedMethod[]
     */
    public function collectData(Node $node, Scope $scope): ?array
    {
        $classReflection = $scope->getClassReflection();
        if ($classReflection === null) {
            return null;
        }
        $actualClassName = $classReflection->getName();

        $methodName = $scope->getFunctionName();
        if ($methodName === null) {
            return null;
        }

        if (!$node->getNode() instanceof ClassMethod &&
           !$node->getNode() instanceof Return_ &&
           !$node->getNode() instanceof Throw_
        ) {
            return null;
        }

        return [new CollectedMethod(
            $actualClassName,
            $methodName,
            $node->getStatementResult()->isAlwaysTerminating()
        )];
    }
}
