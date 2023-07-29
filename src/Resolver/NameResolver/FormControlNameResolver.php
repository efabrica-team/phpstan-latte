<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Resolver\NameResolver;

use Efabrica\PHPStanLatte\Resolver\ValueResolver\ValueResolver;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\Variable;
use PHPStan\Analyser\Scope;
use PHPStan\Type\IntegerRangeType;

final class FormControlNameResolver
{
    private bool $featureTransformDynamicFormControlNamesToString;

    private ValueResolver $valueResolver;

    private NameResolver $nameResolver;

    public function __construct(
        bool $featureTransformDynamicFormControlNamesToString,
        ValueResolver $valueResolver,
        NameResolver $nameResolver
    ) {
        $this->featureTransformDynamicFormControlNamesToString = $featureTransformDynamicFormControlNamesToString;
        $this->valueResolver = $valueResolver;
        $this->nameResolver = $nameResolver;
    }

    /**
     * @return array<int|string>|null
     */
    public function resolve(Expr $expr, Scope $scope): ?array
    {
        $type = $scope->getType($expr);
        if ($type instanceof IntegerRangeType) {
            $min = $type->getMin() !== null ? $type->getMin() : $type->getMax();
            $max = $type->getMax() !== null ? $type->getMax() : $type->getMin();

            if ($min === null || $max === null) {
                return null;
            }

            $names = [];
            for ($i = $min; $i <= $max; $i++) {
                $names[] = $i;
            }
        } else {
            $names = $this->valueResolver->resolve($expr, $scope);
        }

        if ($this->featureTransformDynamicFormControlNamesToString && $names === null) {
            if ($expr instanceof Variable) {
                $variableName = $this->nameResolver->resolve($expr);
                $names = ['$' . $variableName];
            } elseif ($expr instanceof PropertyFetch) {
                $varName = $this->nameResolver->resolve($expr->var);
                $propertyName = $this->nameResolver->resolve($expr->name);
                if ($varName === null || $propertyName === null) {
                    return null;
                }
                $names = ['$' . $varName . '->' . $propertyName];
            }
        }

        if ($names === null) {
            return null;
        }

        return array_filter($names, function ($value) {
            return is_string($value) || is_int($value);
        });
    }
}
