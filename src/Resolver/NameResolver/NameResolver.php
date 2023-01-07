<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Resolver\NameResolver;

use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\Variable;
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
            return $node !== '' ? $node : null;
        }
        if ($node instanceof Name || $node instanceof Identifier) {
            return $this->resolve((string)$node);
        }
        if ($node instanceof Variable) {
            return $this->resolve($node->name);
        }
        if ($node instanceof PropertyFetch) {
            return $this->resolve($node->name);
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
