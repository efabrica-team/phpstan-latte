<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Collector;

use Efabrica\PHPStanLatte\Collector\ValueObject\CollectedVariable;
use Efabrica\PHPStanLatte\PhpDoc\LattePhpDocResolver;
use Efabrica\PHPStanLatte\Resolver\NameResolver\NameResolver;
use Efabrica\PHPStanLatte\Template\Variable;
use Efabrica\PHPStanLatte\Type\TypeSerializer;
use PhpParser\Node;
use PhpParser\Node\Stmt\ClassMethod;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Analyser\Scope;
use PHPStan\Parser\Parser;
use PHPStan\Reflection\ReflectionProvider;

/**
 * @phpstan-import-type CollectedVariableArray from CollectedVariable
 * @extends AbstractCollector<ClassMethod, CollectedVariable, CollectedVariableArray>
 */
final class VariableMethodPhpDocCollector extends AbstractCollector
{
    private LattePhpDocResolver $lattePhpDocResolver;

    public function __construct(
        TypeSerializer $typeSerializer,
        NameResolver $nameResolver,
        ReflectionProvider $reflectionProvider,
        Parser $parser,
        NodeScopeResolver $nodeScopeResolver,
        LattePhpDocResolver $lattePhpDocResolver
    ) {
        parent::__construct($typeSerializer, $nameResolver, $reflectionProvider, $parser, $nodeScopeResolver);
        $this->lattePhpDocResolver = $lattePhpDocResolver;
    }

    public function getNodeType(): string
    {
        return ClassMethod::class;
    }

    /**
     * @param ClassMethod $node
     * @phpstan-return null|CollectedVariableArray[]
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
            $variables[$name] = CollectedVariable::build($node, $scope, $name, $type);
        }

        foreach ($lattePhpDoc->getParentClass()->getVariables() as $name => $type) {
            $variables[] = new CollectedVariable($classReflection->getName(), '', new Variable($name, $type));
        }

        return $this->collectItems(array_values($variables));
    }
}
