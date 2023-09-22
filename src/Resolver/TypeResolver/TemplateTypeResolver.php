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
use PHPStan\Type\VerbosityLevel;

final class TemplateTypeResolver
{
    public function resolve(Type $type): bool
    {
        if ($type instanceof UnionType) {
            foreach ($type->getTypes() as $unionType) {
                if ($this->resolve($unionType)) {
                    return true;
                }
            }
        }
        return (new ObjectType('Nette\Application\UI\Template'))->isSuperTypeOf($type)->yes() || (new ObjectType('Nette\Application\UI\ITemplate'))->isSuperTypeOf($type)->yes();
    }

    public function resolveByNodeAndScope(Node $node, Scope $scope): bool
    {
        $var = null;
        if ($node instanceof Variable) {
            $var = $node;
        } elseif ($node instanceof MethodCall) {
            $var = $node->var;
        } elseif ($node instanceof PropertyFetch) {
//            $var = $node->var;
        }

        if ($var === null) {
            return false;
        }

        $type = $scope->getType($var);

        var_dump($type->describe(VerbosityLevel::typeOnly()));

        return $this->resolve($type);
    }
}
