<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\LatteContext;

use Efabrica\PHPStanLatte\Template\Variable;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\Variable as VariableExpr;
use PHPStan\Analyser\Scope;
use PHPStan\Type\ObjectType;
use PHPStan\Type\ThisType;
use PHPStan\Type\Type;
use PHPStan\Type\UnionType;

final class LatteContextHelper
{
    /**
     * @return Variable[]
     */
    public static function variablesFromType(Type $type): array
    {
        $type = $type->toArray();

        $variables = [];

        foreach ($type->getConstantArrays() as $constantArrayType) {
            $keyTypes = $constantArrayType->getKeyTypes();
            $valueTypes = $constantArrayType->getValueTypes();
            $optionalKeys = $constantArrayType->getOptionalKeys();
            foreach ($keyTypes as $k => $arrayKeyType) {
                $constantStringTypes = $arrayKeyType->getConstantStrings();
                foreach ($constantStringTypes as $constantStringType) {
                    $variableName = $constantStringType->getValue();
                    $variables[$variableName] = new Variable($variableName, $valueTypes[$k], in_array($k, $optionalKeys, true));
                }
            }
        }
        return $variables;
    }

    /**
     * @return Variable[]
     */
    public static function variablesFromTemplateType(string $class): array
    {
        $classType = (new ObjectType($class))->toArray();
        return self::variablesFromType($classType);
    }

    /**
     * @return Variable[]
     */
    public static function variablesFromExpr(?Expr $expr, Scope $scope): array
    {
        if ($expr === null) {
            return [];
        }

        return LatteContextHelper::variablesFromType($scope->getType($expr));
    }

    /**
     * @param class-string|class-string[] $classes
     */
    public static function isClass(Node $node, Scope $scope, $classes): bool
    {
        if (!is_array($classes)) {
            $classes = [$classes];
        }

        $var = null;
        if ($node instanceof VariableExpr) {
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
        if ($type instanceof ThisType) {
            $type = $type->getStaticObjectType();
        }

        foreach ($classes as $class) {
            $allowedType = new ObjectType($class);
            if ($type instanceof UnionType) {
                foreach ($type->getTypes() as $unionType) {
                    if ($allowedType->isSuperTypeOf($unionType)->yes()) {
                        return true;
                    }
                }
            }

            if ($allowedType->isSuperTypeOf($type)->yes()) {
                return true;
            }
        }

        return false;
    }
}
