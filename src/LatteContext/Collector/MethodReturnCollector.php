<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\LatteContext\Collector;

use Efabrica\PHPStanLatte\LatteContext\CollectedData\CollectedMethod;
use PhpParser\Node;
use PhpParser\Node\Stmt\Return_;
use PHPStan\Analyser\Scope;

/**
 * @extends AbstractLatteContextCollector<CollectedMethod>
 */
final class MethodReturnCollector extends AbstractLatteContextCollector
{
    public function getNodeTypes(): array
    {
        return [Return_::class];
    }

    /**
     * @param Return_ $node
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

        if ($node->expr === null) {
            return null;
        }

        $returnType = $scope->getType($node->expr);

        if ($returnType->getConstantStrings() === []) {
            return null; // we only use constatn string return types in PathResolver
        }

        return [new CollectedMethod(
            $actualClassName,
            $methodName,
            false,
            $returnType
        )];
    }
}
