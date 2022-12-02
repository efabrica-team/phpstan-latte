<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Resolver\ValueResolver;

use PhpParser\Node\Expr;
use PHPStan\Analyser\Scope;

final class PathResolver
{
    private bool $resolveAllPossiblePaths;

    private ValueResolver $valueResolver;

    public function __construct(bool $resolveAllPossiblePaths, ValueResolver $valueResolver)
    {
        $this->resolveAllPossiblePaths = $resolveAllPossiblePaths;
        $this->valueResolver = $valueResolver;
    }

    /**
     * @return array<string>|null
     * @phpstan-return array<non-empty-string>|null
     */
    public function resolve(Expr $expr, Scope $scope)
    {
        $resultCandidates = $this->valueResolver->resolve($expr, $scope, $this->resolveAllPossiblePaths ? '*' : null);
        if ($resultCandidates === null) {
            return null;
        }
        $resultList = [];
        foreach ($resultCandidates as $result) {
            if (!is_string($result)) {
                continue;
            }
            $result = preg_replace('#\*+#', '*', $result);
            if ($result === null || $result === '' || $result[0] === '*') {
                continue;
            }
            $resultList[] = $result;
        }
        return count($resultList) > 0 ? $resultList : null;
    }
}
