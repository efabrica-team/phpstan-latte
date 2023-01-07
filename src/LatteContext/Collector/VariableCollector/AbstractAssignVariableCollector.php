<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\LatteContext\Collector\VariableCollector;

use Efabrica\PHPStanLatte\LatteContext\CollectedData\CollectedVariable;
use Efabrica\PHPStanLatte\PhpDoc\LattePhpDocResolver;
use Efabrica\PHPStanLatte\Resolver\NameResolver\NameResolver;
use Efabrica\PHPStanLatte\Resolver\TypeResolver\TemplateTypeResolver;
use PhpParser\Node;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\Variable;
use PHPStan\Analyser\Scope;

abstract class AbstractAssignVariableCollector implements VariableCollectorInterface
{
    private LattePhpDocResolver $lattePhpDocResolver;

    private NameResolver $nameResolver;

    private TemplateTypeResolver $templateTypeResolver;

    public function __construct(
        LattePhpDocResolver $lattePhpDocResolver,
        NameResolver $nameResolver,
        TemplateTypeResolver $templateTypeResolver
    ) {
        $this->lattePhpDocResolver = $lattePhpDocResolver;
        $this->nameResolver = $nameResolver;
        $this->templateTypeResolver = $templateTypeResolver;
    }

    public function isSupported(Node $node): bool
    {
        return $node instanceof Assign;
    }

    /**
     * @param Assign $node
     * @return CollectedVariable[]
     */
    public function collect(Node $node, Scope $scope): array
    {
        $lattePhpDoc = $this->lattePhpDocResolver->resolveForNode($node, $scope);
        if ($lattePhpDoc->isIgnored()) {
            return [];
        }

        $variables = $this->collectVariables($node, $scope);
        $variableNames = [];
        foreach ($variables as $variable) {
            $variableNames[] = $variable->getVariableName();
        }

        if ($lattePhpDoc->hasVariables()) {
            $variables = [];
            foreach ($lattePhpDoc->getVariables($variableNames) as $name => $type) {
                $variables[] = CollectedVariable::build($node, $scope, $name, $type);
            }
        }

        return $variables;
    }

    protected function isTemplateType(Node $node, Scope $scope): bool
    {
        $var = null;
        if ($node instanceof Variable) {
            $var = $node;
        } elseif ($node instanceof PropertyFetch) {
            $var = $node->var;
        }

        if ($var === null) {
            return false;
        }

        $assignVariableType = $scope->getType($var);
        return $this->templateTypeResolver->resolve($assignVariableType);
    }

    protected function getVariableName(Node $node): ?string
    {
        return $this->nameResolver->resolve($node);
    }

    /**
     * @return CollectedVariable[]
     */
    abstract protected function collectVariables(Assign $node, Scope $scope): array;
}
