<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Resolver\TypeResolver;

use Efabrica\PHPStanLatte\Resolver\ValueResolver\ValueResolver;
use PhpParser\Node\Expr;
use PHPStan\Analyser\Scope;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Constant\ConstantFloatType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\NullType;
use PHPStan\Type\Type;
use PHPStan\Type\UnionType;

final class TypeResolver
{
    private ValueResolver $valueResolver;

    public function __construct(ValueResolver $valueResolver)
    {
        $this->valueResolver = $valueResolver;
    }

    public function resolveAsConstantType(Expr $expr, Scope $scope): ?Type
    {
        $values = $this->valueResolver->resolve($expr, $scope);
        if ($values === null) {
            return null;
        }
        $types = [];
        foreach ($values as $value) {
            if (is_string($value)) {
                $types[] = new ConstantStringType($value);
            } elseif (is_int($value)) {
                $types[] = new ConstantIntegerType($value);
            } elseif (is_float($value)) {
                $types[] = new ConstantFloatType($value);
            } elseif (is_bool($value)) {
                $types[] = new ConstantBooleanType($value);
            } elseif (is_null($value)) {
                $types[] = new NullType();
            } else {
                return null; // contains value not convertible to constant type
            }
        }
        return count($types) > 1 ? new UnionType($types) : $types[0];
    }
}
