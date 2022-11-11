<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\LatteTemplateResolver\Finder\NodeVisitor;

use Efabrica\PHPStanLatte\Resolver\TypeResolver\TemplateTypeResolver;
use Efabrica\PHPStanLatte\Template\Variable as TemplateVariable;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\Variable;
use PhpParser\NodeVisitorAbstract;
use PHPStan\Analyser\Scope;

final class TemplateVariableFinderNodeVisitor extends NodeVisitorAbstract
{
    private Scope $scope;

    private TemplateTypeResolver $templateTypeResolver;

    /** @var TemplateVariable[] */
    private array $variables = [];

    public function __construct(Scope $scope, TemplateTypeResolver $templateTypeResolver)
    {
        $this->scope = $scope;
        $this->templateTypeResolver = $templateTypeResolver;
    }

    /**
     * TODO we need to go deeper - method calls, parent::methodCalls etc.
     */
    public function enterNode(Node $node): ?Node
    {
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

        $variableType = $this->scope->getType($var);
        if (!$this->templateTypeResolver->resolve($variableType)) {
            return null;
        }

        $variableName = is_string($nameNode) ? $nameNode : $nameNode->name;
        $this->variables[] = new TemplateVariable($variableName, $this->scope->getType($node->expr));
        return null;
    }

    /**
     * @return TemplateVariable[]
     */
    public function getVariables(): array
    {
        return $this->variables;
    }
}
