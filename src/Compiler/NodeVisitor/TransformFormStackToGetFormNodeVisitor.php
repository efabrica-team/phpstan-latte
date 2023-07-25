<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Compiler\NodeVisitor;

use Efabrica\PHPStanLatte\Compiler\Helper\FormHelper;
use Efabrica\PHPStanLatte\Compiler\NodeVisitor\Behavior\ExprTypeNodeVisitorBehavior;
use Efabrica\PHPStanLatte\Compiler\NodeVisitor\Behavior\ExprTypeNodeVisitorInterface;
use Efabrica\PHPStanLatte\Resolver\NameResolver\NameResolver;
use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\ArrayDimFetch;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Name;
use PhpParser\NodeVisitorAbstract;

/**
 * changed output from:
 * <code>
 * $form = $this->global->formsStack[] = $this->global->uiControl[$formName];
 * </code>
 *
 * to:
 * <code>
 * $form = FormHelper::getForm($this->global->formNamesToFormClasses[$formName]);
 * </code>
 */
final class TransformFormStackToGetFormNodeVisitor extends NodeVisitorAbstract implements ExprTypeNodeVisitorInterface
{
    use ExprTypeNodeVisitorBehavior;

    private NameResolver $nameResolver;

    public function __construct(NameResolver $nameResolver)
    {
        $this->nameResolver = $nameResolver;
    }

    public function enterNode(Node $node): ?Node
    {
        if (!$node instanceof Assign) {
            return null;
        }

        if ($this->nameResolver->resolve($node->var) !== 'form') {
            return null;
        }

        if (!$node->expr instanceof Assign) {
            return null;
        }

        if (!$node->expr->expr instanceof ArrayDimFetch) {
            return null;
        }

        /** @var ArrayDimFetch $dim */
        $dim = $node->expr->expr;

        if (!$dim->dim instanceof Variable) {
            return null;
        }

        $dimType = $this->getType($dim->dim);
        if (!$dimType) {
            return null;
        }

        return new Assign($node->var, new StaticCall(new Name(FormHelper::class), 'getForm', [
            new Arg(new ArrayDimFetch(new PropertyFetch(new PropertyFetch(new Variable('this'), 'global'), 'formNamesToFormClasses'), $dim->dim)),
        ]), $node->getAttributes());
    }
}
