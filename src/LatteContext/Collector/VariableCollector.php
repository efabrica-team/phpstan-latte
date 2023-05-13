<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\LatteContext\Collector;

use Efabrica\PHPStanLatte\LatteContext\CollectedData\CollectedError;
use Efabrica\PHPStanLatte\LatteContext\CollectedData\CollectedVariable;
use Efabrica\PHPStanLatte\LatteContext\Collector\VariableCollector\VariableCollectorInterface;
use Efabrica\PHPStanLatte\PhpDoc\LattePhpDocResolver;
use Efabrica\PHPStanLatte\Resolver\NameResolver\NameResolver;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\TypeCombinator;

/**
 * @extends AbstractLatteContextCollector<CollectedVariable>
 */
final class VariableCollector extends AbstractLatteContextCollector
{
    private LattePhpDocResolver $lattePhpDocResolver;

    /** @var VariableCollectorInterface[] */
    private array $variableCollectors;

    /**
     * @param VariableCollectorInterface[] $variableCollectors
     */
    public function __construct(
        NameResolver $nameResolver,
        ReflectionProvider $reflectionProvider,
        LattePhpDocResolver $lattePhpDocResolver,
        array $variableCollectors
    ) {
        parent::__construct($nameResolver, $reflectionProvider);
        $this->variableCollectors = $variableCollectors;
        $this->lattePhpDocResolver = $lattePhpDocResolver;
    }

    public function getNodeTypes(): array
    {
        $nodeTypes = [];
        foreach ($this->variableCollectors as $collector) {
            $nodeTypes = array_merge($nodeTypes, $collector->getNodeTypes());
        }
        return array_unique($nodeTypes);
    }

    /**
     * @phpstan-return null|array<CollectedVariable|CollectedError>
     */
    public function collectData(Node $node, Scope $scope): ?array
    {
        $classReflection = $scope->getTraitReflection() ?: $scope->getClassReflection();
        if ($classReflection === null) {
            return null;
        }

        $functionName = $scope->getFunctionName();
        if ($functionName === null) {
            return null;
        }

        if ($this->lattePhpDocResolver->resolveForNode($node, $scope)->isIgnored()) {
            return null;
        }

        $isVariablesNode = false;
        $collectedVariables = [];
        $collectedErrors = [];
        foreach ($this->variableCollectors as $variableCollector) {
            if (!$variableCollector->isSupported($node)) {
                continue;
            }
            $variables = $variableCollector->collect($node, $scope);
            if ($variables === null) {
                continue;
            }
            $isVariablesNode = true;
            foreach ($variables as $variable) {
                if ($variable instanceof CollectedError) {
                    $collectedErrors[] = $variable;
                    continue;
                }
                $name = $variable->getVariableName();
                if (isset($collectedVariables[$name])) {
                    $type = TypeCombinator::union($collectedVariables[$name]->getVariableType(), $variable->getVariableType());
                    $collectedVariables[$name] = CollectedVariable::build($node, $scope, $name, $type);
                } else {
                    $collectedVariables[$name] = $variable;
                }
            }
        }

        if (!$isVariablesNode) {
            return null;
        }

        $lattePhpDoc = $this->lattePhpDocResolver->resolveForNode($node, $scope);
        if ($lattePhpDoc->hasVariables()) {
            foreach ($lattePhpDoc->getVariables(array_keys($collectedVariables)) as $name => $type) {
                $collectedVariables[$name] = CollectedVariable::build($node, $scope, $name, $type);
            }
        }

        return array_values(array_merge($collectedVariables, $collectedErrors));
    }
}
