<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Collector;

use Efabrica\PHPStanLatte\Collector\ValueObject\CollectedVariable;
use Efabrica\PHPStanLatte\Resolver\TypeResolver\TemplateTypeResolver;
use Efabrica\PHPStanLatte\Template\Variable as TemplateVariable;
use Efabrica\PHPStanLatte\Type\TypeSerializer;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\Variable;
use PHPStan\Analyser\Scope;

/**
 * @phpstan-import-type CollectedVariableArray from CollectedVariable
 * @extends AbstractCollector<Node, CollectedVariable, CollectedVariableArray>
 */
final class VariableCollector extends AbstractCollector
{
    private TemplateTypeResolver $templateTypeResolver;

    public function __construct(TypeSerializer $typeSerializer, TemplateTypeResolver $templateTypeResolver)
    {
        parent::__construct($typeSerializer);
        $this->templateTypeResolver = $templateTypeResolver;
    }

    public function getNodeType(): string
    {
        return Node::class;
    }

    /**
     * @phpstan-return null|CollectedVariableArray[]
     */
    public function processNode(Node $node, Scope $scope): ?array
    {
        $classReflection = $scope->getClassReflection();
        if ($classReflection === null) {
            return null;
        }

        $functionName = $scope->getFunctionName();
        if ($functionName === null) {
            return null;
        }

        // TODO add other variable assign resolvers - $template->setParameters(), $template->render(path, parameters) etc.

        if (!$node instanceof Assign) {
            return null;
        }

        if ($node->var instanceof Variable) {
            $var = $node->var;
            $nameNode = $node->var->name;
        } elseif ($node->var instanceof PropertyFetch) {
            $var = $node->var->var;
            $nameNode = $node->var->name;
        } else {
            return null;
        }

        if ($nameNode instanceof Expr) {
            return null;
        }

        $assignVariableType = $scope->getType($var);
        if (!$this->templateTypeResolver->resolve($assignVariableType)) {
            return null;
        }

        $variableName = is_string($nameNode) ? $nameNode : $nameNode->name;
        return $this->collectItem(new CollectedVariable(
            $classReflection->getName(),
            $functionName,
            new TemplateVariable($variableName, $scope->getType($node->expr))
        ));
    }
}
