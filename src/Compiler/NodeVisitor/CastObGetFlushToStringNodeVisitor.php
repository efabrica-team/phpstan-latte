<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Compiler\NodeVisitor;

use Efabrica\PHPStanLatte\Resolver\NameResolver\NameResolver;
use PhpParser\Node;
use PhpParser\Node\Expr\Cast\String_;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\NodeVisitorAbstract;

final class CastObGetFlushToStringNodeVisitor extends NodeVisitorAbstract
{
    private NameResolver $nameResolver;

    public function __construct(NameResolver $nameResolver)
    {
        $this->nameResolver = $nameResolver;
    }

    public function leaveNode(Node $node): ?Node
    {
        if (!$node instanceof FuncCall) {
            return null;
        }

        if ($this->nameResolver->resolve($node) !== 'ob_get_flush') {
            return null;
        }

        return new String_($node, $node->getAttributes());
    }
}
