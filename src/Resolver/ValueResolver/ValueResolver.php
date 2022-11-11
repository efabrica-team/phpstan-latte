<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Resolver\ValueResolver;

use PhpParser\ConstExprEvaluationException;
use PhpParser\ConstExprEvaluator;
use PhpParser\Node\Expr;
use PhpParser\Node\Scalar\MagicConst\Dir;
use PHPStan\Analyser\Scope;

final class ValueResolver
{
    /**
     * @return mixed
     */
    public function resolve(Expr $expr, Scope $scope)
    {
        $constExprEvaluator = new ConstExprEvaluator(function (Expr $expr) use ($scope) {
            if ($expr instanceof Dir) {
                return dirname($scope->getFile());
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
