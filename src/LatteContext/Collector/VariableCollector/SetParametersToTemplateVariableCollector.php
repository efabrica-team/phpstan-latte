<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\LatteContext\Collector\VariableCollector;

use Efabrica\PHPStanLatte\LatteContext\CollectedData\CollectedVariable;
use Efabrica\PHPStanLatte\LatteContext\Collector\AbstractLatteContextSubCollector;
use Efabrica\PHPStanLatte\LatteContext\LatteContextHelper;
use Efabrica\PHPStanLatte\Resolver\NameResolver\NameResolver;
use Efabrica\PHPStanLatte\Resolver\TypeResolver\TemplateTypeResolver;
use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;

/**
 * @extends AbstractLatteContextSubCollector<CollectedVariable>
 */
final class SetParametersToTemplateVariableCollector extends AbstractLatteContextSubCollector implements VariableCollectorInterface
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

    public function getNodeTypes(): array
    {
        return [MethodCall::class];
    }

    /**
     * @param MethodCall $node
     */
    public function collect(Node $node, Scope $scope): ?array
    {
        if ($this->nameResolver->resolve($node) !== 'setParameters') {
            return null;
        }

        if (!$this->templateTypeResolver->resolveByNodeAndScope($node, $scope)) {
            return null;
        }

        if (!isset($node->args[0]) || !$node->args[0] instanceof Arg) {
            return [];
        }

        return CollectedVariable::buildAll($node, $scope, LatteContextHelper::variablesFromExpr($node->args[0]->value, $scope));
    }
}
