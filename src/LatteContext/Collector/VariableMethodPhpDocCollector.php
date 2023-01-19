<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\LatteContext\Collector;

use Efabrica\PHPStanLatte\LatteContext\CollectedData\CollectedVariable;
use Efabrica\PHPStanLatte\PhpDoc\LattePhpDocResolver;
use Efabrica\PHPStanLatte\Resolver\NameResolver\NameResolver;
use Efabrica\PHPStanLatte\Template\Variable;
use PhpParser\Node;
use PhpParser\Node\Stmt\ClassMethod;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;

/**
 * @extends AbstractLatteContextCollector<CollectedVariable>
 */
final class VariableMethodPhpDocCollector extends AbstractLatteContextCollector
{
    private LattePhpDocResolver $lattePhpDocResolver;

    public function __construct(
        NameResolver $nameResolver,
        ReflectionProvider $reflectionProvider,
        LattePhpDocResolver $lattePhpDocResolver
    ) {
        parent::__construct($nameResolver, $reflectionProvider);
        $this->lattePhpDocResolver = $lattePhpDocResolver;
    }

    public function getNodeTypes(): array
    {
        return [ClassMethod::class];
    }

    /**
     * @param ClassMethod $node
     * @phpstan-return null|CollectedVariable[]
     */
    public function collectData(Node $node, Scope $scope): ?array
    {
        $classReflection = $scope->getClassReflection();
        if ($classReflection === null) {
            return null;
        }

        $methodName = $this->nameResolver->resolve($node);
        if ($methodName === null) {
            return null;
        }

        $lattePhpDoc = $this->lattePhpDocResolver->resolveForMethod($classReflection->getName(), $methodName);
        if ($lattePhpDoc->isIgnored()) {
            return null;
        }

        $variables = [];
        foreach ($lattePhpDoc->getVariablesWithParents() as $name => $type) {
            $variables[$name] = CollectedVariable::build($node, $scope, $name, $type, true);
        }

        foreach ($lattePhpDoc->getParentClass()->getVariables() as $name => $type) {
            $variables[] = new CollectedVariable($classReflection->getName(), '', new Variable($name, $type), true);
        }

        return array_values($variables);
    }
}
