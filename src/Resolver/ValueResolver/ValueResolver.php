<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Resolver\ValueResolver;

use PhpParser\ConstExprEvaluationException;
use PhpParser\ConstExprEvaluator;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Cast;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\MagicConst\Dir;
use PhpParser\Node\Scalar\MagicConst\File;

final class ValueResolver
{
    /**
     * @param mixed $unknownValuePlaceholder
     * @return mixed
     */
    public function resolve(Expr $expr, ?string $actualFile = null, $unknownValuePlaceholder = null)
    {
        $constExprEvaluator = new ConstExprEvaluator(function (Expr $expr) use ($actualFile, $unknownValuePlaceholder) {
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
                return $this->resolve($expr->expr, $actualFile, $unknownValuePlaceholder);
            }

            if ($expr instanceof Variable && $unknownValuePlaceholder) {
                return $unknownValuePlaceholder;
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
                    $arguments[] = $this->resolve($arg->value, $actualFile);
                }

                return call_user_func_array($functionName, $arguments);
            }

            throw new ConstExprEvaluationException();
        });

        try {
            return $constExprEvaluator->evaluateDirectly($expr);
        } catch (ConstExprEvaluationException $e) {
            return null;
        }
    }
}
