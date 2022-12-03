<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Resolver\ValueResolver;

use Nette\Utils\Finder;
use Nette\Utils\Strings;
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

    /**
     * @param ?string $path
     * @return ?array<?string>
     */
    public function expand(?string $path): ?array
    {
        if ($path === null || strpos($path, '*') === false) {
            return [$path];
        }

        $dirWithoutWildcards = (string)Strings::before((string)Strings::before($path, '*'), '/', -1);
        $pattern = substr($path, strlen($dirWithoutWildcards) + 1);

        $paths = [];
        /** @var string $file */
        foreach (Finder::findFiles($pattern)->from($dirWithoutWildcards) as $file) {
            $paths[] = (string)$file;
        }
        return count($paths) > 0 ? $paths : null;
    }
}
