<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\LatteContext\Collector;

use Efabrica\PHPStanLatte\LatteContext\CollectedData\CollectedVariable;
use Efabrica\PHPStanLatte\LatteContext\Collector\VariableCollector\VariableCollectorInterface;
use Efabrica\PHPStanLatte\Resolver\NameResolver\NameResolver;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;

/**
 * @extends AbstractLatteContextCollector<Node, CollectedVariable>
 */
final class VariableCollector extends AbstractLatteContextCollector
{
    /** @var VariableCollectorInterface[] */
    private array $variableCollectors;

    /**
     * @param VariableCollectorInterface[] $variableCollectors
     */
    public function __construct(
        NameResolver $nameResolver,
        ReflectionProvider $reflectionProvider,
        array $variableCollectors
    ) {
        parent::__construct($nameResolver, $reflectionProvider);
        $this->variableCollectors = $variableCollectors;
    }

    public function getNodeType(): string
    {
        return Node::class;
    }

    /**
     * @phpstan-return null|CollectedVariable[]
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

        $collectedVariables = [];

        // TODO add other variable assign resolvers - $template->setParameters(), $template->render(path, parameters) etc.

        foreach ($this->variableCollectors as $variableCollector) {
            if (!$variableCollector->isSupported($node)) {
                continue;
            }
            $collectedVariables[] = $variableCollector->collect($node, $scope);
        }
        return array_merge(...$collectedVariables);
    }
}
