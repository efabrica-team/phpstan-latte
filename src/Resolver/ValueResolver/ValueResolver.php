<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Resolver\ValueResolver;

use PhpParser\ConstExprEvaluationException;
use PhpParser\ConstExprEvaluator;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\BinaryOp\Coalesce;
use PhpParser\Node\Expr\Cast;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\Encapsed;
use PhpParser\Node\Scalar\EncapsedStringPart;
use PhpParser\Node\Scalar\MagicConst\Dir;
use PhpParser\Node\Scalar\MagicConst\File;
use PHPStan\Analyser\Scope;
use PHPStan\Type\UnionType;
use ReflectionMethod;
use function count;
use function is_callable;
use function method_exists;

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

            $constantScalarValues = $type->getConstantScalarValues();

            if (count($constantScalarValues) === 1) {
                return $constantScalarValues[0];
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

            if ($expr instanceof Coalesce) {
                $leftVal = $this->resolve($expr->left, $scope, $fallbackEvaluator);
                if ($leftVal !== null) {
                    return $leftVal;
                }
                $rightVal = $this->resolve($expr->right, $scope, $fallbackEvaluator);
                if ($rightVal !== null) {
                    return $rightVal;
                }
                return null;
            }

            if ($expr instanceof Cast) {
                $options = $this->resolve($expr->expr, $scope, $fallbackEvaluator);
                if ($options === null || count($options) !== 1) {
                    throw new ConstExprEvaluationException();
                }
                return $options[0];
            }

            if ($expr instanceof Encapsed) {
                $result = [];
                foreach ($expr->parts as $part) {
                    if ($part instanceof EncapsedStringPart) {
                        $options = [$part->value];
                    } else {
                        $options = $this->resolve($part, $scope, $fallbackEvaluator);
                    }
                    if ($options === null || count($options) !== 1) {
                        throw new ConstExprEvaluationException();
                    }
                    $result[] = $options[0];
                }
                return implode('', $result);
            }

            if ($expr instanceof FuncCall && $expr->name instanceof Name) {
                $functionName = (string)$expr->name;

                $args = $expr->getArgs();
                $arguments = [];
                $argsValid = true;
                foreach ($args as $arg) {
                    $options = $this->resolve($arg->value, $scope);
                    if ($options === null || count($options) !== 1) {
                        $argsValid = false;
                    } else {
                        $arguments[] = $options[0];
                    }
                }

                if ($argsValid && function_exists($functionName)) {
                    return call_user_func_array($functionName, $arguments);
                }
            }

            if ($expr instanceof StaticCall && $expr->name instanceof Identifier && $expr->class instanceof Name) {
                $className = (string)$expr->class;
                $methodName = (string)$expr->name;

                $classReflection = $scope->getClassReflection();

                if ($classReflection) {
                    if ($className === 'self' || $className === 'static') {
                        $className = $classReflection->getName();
                    } elseif ($className === 'parent' && $classReflection->getParentClass()) {
                        $className = $classReflection->getParentClass()->getName();
                    }
                }

                $callable = [$className, $methodName];

                $args = $expr->getArgs();
                $arguments = [];
                $argsValid = true;
                foreach ($args as $arg) {
                    $options = $this->resolve($arg->value, $scope);
                    if ($options === null || count($options) !== 1) {
                        $argsValid = false;
                    } else {
                        $arguments[] = $options[0];
                    }
                }

                if ($argsValid && method_exists($className, $methodName) && is_callable($callable)) {
                    $ref = new ReflectionMethod($className, $methodName);
                    if ($ref->isPublic()) {
                        return call_user_func_array($callable, $arguments);
                    }
                }
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
                $constantScalarValues = $subType->getConstantScalarValues();
                if (count($constantScalarValues) !== 1) {
                    return null;
                }
                $options[] = $constantScalarValues[0];
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
