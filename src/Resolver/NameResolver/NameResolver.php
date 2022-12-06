<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Resolver\NameResolver;

use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\ClassMethod;

final class NameResolver
{
    /**
     * @param Node|null|string $node
     * @return string|null
     */
    public function resolve($node): ?string
    {
        if ($node === null) {
            return null;
        }
        if (is_string($node)) {
            return $node;
        }
        if ($node instanceof Name || $node instanceof Identifier) {
            return (string)$node;
        }
        if ($node instanceof FuncCall || $node instanceof MethodCall || $node instanceof StaticCall) {
            return $this->resolve($node->name);
        }
        if ($node instanceof ClassMethod) {
            return $this->resolve($node->name);
        }

        return null;
    }
}
