<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Collector;

use Efabrica\PHPStanLatte\Collector\ValueObject\CollectedMethod;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\ExecutionEndNode;

/**
 * @phpstan-import-type CollectedMethodArray from CollectedMethod
 * @extends AbstractCollector<ExecutionEndNode, CollectedMethod, CollectedMethodArray>
 */
final class MethodCollector extends AbstractCollector
{
    public function getNodeType(): string
    {
        return ExecutionEndNode::class;
    }

    /**
     * @param ExecutionEndNode $node
     * @phpstan-return null|CollectedMethodArray[]
     */
    public function processNode(Node $node, Scope $scope): ?array
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

        return $this->collectItem(new CollectedMethod(
            $actualClassName,
            $methodName,
            $node->getStatementResult()->isAlwaysTerminating()
        ));
    }
}
