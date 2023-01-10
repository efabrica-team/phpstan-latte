<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\LatteContext\Collector;

use Efabrica\PHPStanLatte\LatteContext\CollectedData\CollectedMethod;
use PhpParser\Node;
use PhpParser\Node\Stmt\Return_;
use PHPStan\Analyser\Scope;

/**
 * @extends AbstractLatteContextCollector<Return_, CollectedMethod>
 */
final class MethodReturnCollector extends AbstractLatteContextCollector
{
    public function getNodeType(): string
    {
        return Return_::class;
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

        return [new CollectedMethod(
            $actualClassName,
            $methodName,
            false,
            $scope->getType($node->expr)
        )];
    }
}
