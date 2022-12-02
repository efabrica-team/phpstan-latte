<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Resolver\ValueResolver;

use PhpParser\Node\Expr;

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
     * @return ?string
     * @phpstan-return ?non-empty-string
     */
    public function resolve(Expr $expr, ?string $actualFile = null)
    {
        $result = $this->valueResolver->resolve($expr, $actualFile, $this->resolveAllPossiblePaths ? '*' : null);
        if (!is_string($result)) {
            return null;
        }
        $result = preg_replace('#\*+#', '*', $result);
        if ($result === null || $result === '' || $result[0] === '*') {
            return null;
        }
        return $result;
    }
}
