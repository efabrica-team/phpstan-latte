<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Compiler\NodeVisitor;

use Efabrica\PHPStanLatte\Resolver\NameResolver\NameResolver;
use PhpParser\Node;
use PhpParser\Node\Expr\ArrayDimFetch;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Scalar\LNumber;
use PhpParser\NodeVisitorAbstract;

/**
 * changed output from:
 * <code>
 * if ($ʟ_if[0] = $nullOrUrl) {
 * </code>
 *
 * to:
 * <code>
 * if ($ʟ_if0 = $nullOrUrl) {
 * </code>
 */
final class ChangeLifArrayToLifVariableNodeVisitor extends NodeVisitorAbstract
{
    private NameResolver $nameResolver;

    public function __construct(NameResolver $nameResolver)
    {
        $this->nameResolver = $nameResolver;
    }

    public function leaveNode(Node $node): ?Node
    {
        if (!$node instanceof ArrayDimFetch) {
            return null;
        }

        $arrayDimFetchVarName = $this->nameResolver->resolve($node->var);
        if ($arrayDimFetchVarName !== 'ʟ_if') {
            return null;
        }

        if (!$node->dim instanceof LNumber) {
            return null;
        }

        return new Variable('ʟ_if' . $node->dim->value);
    }
}
