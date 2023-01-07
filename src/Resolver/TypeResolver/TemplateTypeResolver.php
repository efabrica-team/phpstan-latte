<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Resolver\TypeResolver;

use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\Variable;
use PHPStan\Analyser\Scope;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\UnionType;

final class TemplateTypeResolver
{
    public function resolve(Type $type): bool
    {
        if ($type instanceof ObjectType) {
            return $type->isInstanceOf('Nette\Application\UI\Template')->yes() || $type->isInstanceOf('Nette\Application\UI\ITemplate')->yes();
        } elseif ($type instanceof UnionType) {
            foreach ($type->getTypes() as $unionType) {
                if ($this->resolve($unionType)) {
                    return true;
                }
            }
        }
        return false;
    }

    public function resolveByNodeAndScope(Node $node, Scope $scope): bool
    {
        $var = null;
        if ($node instanceof Variable) {
            $var = $node;
        } elseif ($node instanceof MethodCall) {
            $var = $node->var;
        } elseif ($node instanceof PropertyFetch) {
            $var = $node->var;
        }

        if ($var === null) {
            return false;
        }

        $type = $scope->getType($var);
        return $this->resolve($type);
    }
}
