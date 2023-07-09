<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Compiler\NodeVisitor;

use Efabrica\PHPStanLatte\Resolver\NameResolver\NameResolver;
use PhpParser\Node;
use PhpParser\Node\Expr\ArrayDimFetch;
use PhpParser\Node\Expr\Ternary;
use PhpParser\Node\Expr\Variable;
use PhpParser\NodeVisitorAbstract;

/**
 * changed output from:
 * <code>
 * echo \Latte\Runtime\Filters::htmlAttributes(isset($ʟ_tmp[0]) && \is_array($ʟ_tmp['0']) ? $ʟ_tmp['0'] : $ʟ_tmp);
 * </code>
 *
 * to:
 * <code>
 * echo \Latte\Runtime\Filters::htmlAttributes($ʟ_tmp);
 * </code>
 */
final class TransformNAttrNodeVisitor extends NodeVisitorAbstract
{
    private NameResolver $nameResolver;

    public function __construct(NameResolver $nameResolver)
    {
        $this->nameResolver = $nameResolver;
    }

    public function enterNode(Node $node): ?Node
    {
        if (!$node instanceof Ternary) {
            return null;
        }

        if (!$node->if instanceof ArrayDimFetch) {
            return null;
        }

        if (!$node->else instanceof Variable) {
            return null;
        }

        $variable = $node->else;
        if ($this->nameResolver->resolve($variable) !== 'ʟ_tmp') {
            return null;
        }

        return new Variable('ʟ_tmp');
    }
}
