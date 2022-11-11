<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Resolver\NameResolver;

use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;

final class NameResolver
{
    public function resolve(Node $node): ?string
    {
        if ($node instanceof Name || $node instanceof Identifier) {
            return (string)$node;
        }
        if ($node instanceof FuncCall) {
            return $this->resolve($node->name);
        }

        return null;
    }
}
