<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\LatteContext\Collector\VariableCollector;

use Efabrica\PHPStanLatte\LatteContext\CollectedData\CollectedVariable;
use Efabrica\PHPStanLatte\Resolver\NameResolver\NameResolver;
use Efabrica\PHPStanLatte\Resolver\TypeResolver\TemplateTypeResolver;
use Efabrica\PHPStanLatte\Resolver\ValueResolver\ValueResolver;
use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;

final class AddToTemplateVariableCollector implements VariableCollectorInterface
{
    private NameResolver $nameResolver;

    private ValueResolver $valueResolver;

    private TemplateTypeResolver $templateTypeResolver;

    public function __construct(
        NameResolver $nameResolver,
        ValueResolver $valueResolver,
        TemplateTypeResolver $templateTypeResolver
    ) {
        $this->nameResolver = $nameResolver;
        $this->valueResolver = $valueResolver;
        $this->templateTypeResolver = $templateTypeResolver;
    }

    public function getNodeTypes(): array
    {
        return [MethodCall::class];
    }

    /**
     * @param MethodCall $node
     */
    public function collect(Node $node, Scope $scope): ?array
    {
        if ($this->nameResolver->resolve($node) !== 'add') {
            return null;
        }

        if (!$this->templateTypeResolver->resolveByNodeAndScope($node, $scope)) {
            return null;
        }

        $args = $node->getArgs();
        if (count($args) !== 2) {
            return null;
        }

        $names = $this->valueResolver->resolveStrings($args[0]->value, $scope);
        if ($names === null) {
            return null;
        }

        $type = $scope->getType($args[1]->value);
        $variables = [];
        foreach ($names as $name) {
            $variables[] = CollectedVariable::build($node, $scope, $name, $type);
        }
        return $variables;
    }
}
