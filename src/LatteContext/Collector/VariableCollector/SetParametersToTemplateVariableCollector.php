<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\LatteContext\Collector\VariableCollector;

use Efabrica\PHPStanLatte\LatteContext\CollectedData\CollectedVariable;
use Efabrica\PHPStanLatte\Resolver\NameResolver\NameResolver;
use Efabrica\PHPStanLatte\Resolver\TypeResolver\TemplateTypeResolver;
use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Type\Constant\ConstantArrayType;

final class SetParametersToTemplateVariableCollector implements VariableCollectorInterface
{
    private NameResolver $nameResolver;

    private TemplateTypeResolver $templateTypeResolver;

    public function __construct(
        NameResolver $nameResolver,
        TemplateTypeResolver $templateTypeResolver
    ) {
        $this->nameResolver = $nameResolver;
        $this->templateTypeResolver = $templateTypeResolver;
    }

    public function isSupported(Node $node): bool
    {
        return $node instanceof MethodCall;
    }

    /**
     * @param MethodCall $node
     */
    public function collect(Node $node, Scope $scope): array
    {
        if ($this->nameResolver->resolve($node) !== 'setParameters') {
            return [];
        }

        if (!$this->templateTypeResolver->resolveByNodeAndScope($node, $scope)) {
            return [];
        }

        if (!isset($node->args[0]) || !$node->args[0] instanceof Arg) {
            return [];
        }

        $types = $scope->getType($node->args[0]->value);
        if (!$types instanceof ConstantArrayType) {
            return [];
        }
        $parameterNames = [];
        foreach ($types->getKeyTypes() as $keyType) {
            $parameterNames[] = (string)$keyType->getValue();
        }

        $variables = [];
        foreach ($types->getValueTypes() as $i => $valueType) {
            if (!isset($parameterNames[$i])) {
                continue;
            }
            $variables[] = CollectedVariable::build($node, $scope, $parameterNames[$i], $valueType);
        }
        return $variables;
    }
}
