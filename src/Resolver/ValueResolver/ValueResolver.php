<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Resolver\ValueResolver;

use PhpParser\ConstExprEvaluationException;
use PhpParser\ConstExprEvaluator;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Cast;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\MagicConst\Dir;
use PhpParser\Node\Scalar\MagicConst\File;
use PHPStan\Analyser\Scope;
use PHPStan\Type\ConstantScalarType;
use PHPStan\Type\UnionType;

final class ValueResolver
{
    /**
     * @param callable(Expr, Scope): mixed $fallbackEvaluator
     * @return mixed[]|null
     */
    public function resolve(Expr $expr, Scope $scope, $fallbackEvaluator = null)
    {
        $constExprEvaluator = new ConstExprEvaluator(function (Expr $expr) use ($scope, $fallbackEvaluator) {
            $actualFile = $scope->getFile();

            $type = $scope->getType($expr);

            if ($type instanceof ConstantScalarType) {
                return $type->getValue();
            }

            if ($expr instanceof Dir) {
                return $actualFile ? dirname($actualFile) : null;
            }

            if ($expr instanceof File) {
                return $actualFile;
            }

            if ($expr instanceof ConstFetch) {
                return constant((string)$expr->name);
            }

            if ($expr instanceof Cast) {
                $options = $this->resolve($expr->expr, $scope, $fallbackEvaluator);
                if ($options === null || count($options) !== 1) {
                    throw new ConstExprEvaluationException();
                }
                return $options[0];
            }

            if ($expr instanceof FuncCall) {
                if (!$expr->name instanceof Name) {
                    return null;
                }

                $functionName = (string)$expr->name;
                if (!function_exists($functionName)) {
                    return null;
                }

                $args = $expr->getArgs();
                $arguments = [];
                foreach ($args as $arg) {
                    $options = $this->resolve($arg->value, $scope);
                    if ($options === null || count($options) !== 1) {
                        throw new ConstExprEvaluationException();
                    }
                    $arguments[] = $options[0];
                }

                return call_user_func_array($functionName, $arguments);
            }

            if ($fallbackEvaluator !== null) {
                return $fallbackEvaluator($expr, $scope);
            } else {
                throw new ConstExprEvaluationException();
            }
        });

        $type = $scope->getType($expr);
        if ($type instanceof UnionType) {
            $options = [];
            foreach ($type->getTypes() as $subType) {
                if (!$subType instanceof ConstantScalarType) {
                    return null;
                }
                $options[] = $subType->getValue();
            }
            return $options;
        }

        try {
            return [$constExprEvaluator->evaluateDirectly($expr)];
        } catch (ConstExprEvaluationException $e) {
            return null;
        }
    }

    /**
     * @return string[]|null
     */
    public function resolveStrings(Expr $expr, Scope $scope): ?array
    {
        $values = $this->resolve($expr, $scope);
        if ($values === null) {
            return null;
        }
        return array_filter($values, 'is_string');
    }
}
