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

final class ValueResolver
{
    /**
     * @return mixed
     */
    public function resolve(Expr $expr, ?string $actualFile = null)
    {
        $constExprEvaluator = new ConstExprEvaluator(function (Expr $expr) use ($actualFile) {
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
                return $this->resolve($expr->expr, $actualFile);
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

            return null;
        });

        try {
            return $constExprEvaluator->evaluateDirectly($expr);
        } catch (ConstExprEvaluationException $e) {
            return null;
        }
    }
}
