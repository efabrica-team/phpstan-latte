<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Compiler\NodeVisitor;

use Efabrica\PHPStanLatte\Compiler\NodeVisitor\Behavior\ExprTypeNodeVisitorBehavior;
use Efabrica\PHPStanLatte\Compiler\NodeVisitor\Behavior\ExprTypeNodeVisitorInterface;
use Efabrica\PHPStanLatte\Resolver\NameResolver\NameResolver;
use PhpParser\Node;
use PhpParser\Node\Expr\ArrayDimFetch;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Scalar\String_;
use PhpParser\NodeVisitorAbstract;

/**
 * changed output from:
 * <code>
 * $form[$dynamicInput];
 * </code>
 *
 * to:
 * <code>
 * $form["$dynamicInput"];
 * </code>
 *
 * if feature flag is turned on and type of $dynamicInput cannot be resolved as constant string / int
 */
final class TransformDynamicFormControlsToDynamicStringNodeVisitor extends NodeVisitorAbstract implements ExprTypeNodeVisitorInterface
{
    use ExprTypeNodeVisitorBehavior;

    private bool $featureTransformDynamicFormControlNamesToString;

    private NameResolver $nameResolver;

    public function __construct(
        bool $featureTransformDynamicFormControlNamesToString,
        NameResolver $nameResolver
    ) {
        $this->featureTransformDynamicFormControlNamesToString = $featureTransformDynamicFormControlNamesToString;
        $this->nameResolver = $nameResolver;
    }

    public function enterNode(Node $node): ?Node
    {
        if ($this->featureTransformDynamicFormControlNamesToString === false) {
            return null;
        }

        if (!$node instanceof ArrayDimFetch) {
            return null;
        }

        if ($this->nameResolver->resolve($node->var) !== 'form') {
            return null;
        }

        if ($node->dim === null) {
            return null;
        }

        $type = $this->getType($node->dim);
        if ($type !== null && $type->getConstantScalarValues() !== []) {
            return null;
        }

        if ($node->dim instanceof Variable) {
            $node->dim = new String_('$' . $this->nameResolver->resolve($node->dim));
        }

        if ($node->dim instanceof PropertyFetch) {
            $varName = $this->nameResolver->resolve($node->dim->var);
            $propertyName = $this->nameResolver->resolve($node->dim->name);
            if ($varName === null || $propertyName === null) {
                return null;
            }
            $node->dim = new String_('$' . $varName . '->' . $propertyName);
        }

        return $node;
    }
}
