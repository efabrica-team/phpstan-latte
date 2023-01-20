<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Resolver\ValueResolver;

use Efabrica\PHPStanLatte\LatteContext\Finder\MethodFinder;
use Nette\Utils\Finder;
use Nette\Utils\Strings;
use PhpParser\ConstExprEvaluationException;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\TypeWithClassName;
use SplFileInfo;

final class PathResolver
{
    private const METHOD_START = '🢡🢡';
    private const METHOD_END = '🢠🢠';
    private const METHOD_SEPARATOR = ' ';

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
        $resultCandidates = $this->valueResolver->resolve(
            $expr,
            $scope,
            function (Expr $expr, Scope $scope) {
                return $this->evaluate($expr, $scope);
            }
        );
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

    private function evaluate(Expr $expr, Scope $scope): string
    {
        if ($expr instanceof Variable && $this->resolveAllPossiblePaths) {
            return '*';
        }
        if ($expr instanceof MethodCall) {
            if (!$expr->name instanceof Identifier) {
                throw new ConstExprEvaluationException();
            }
            $calledOnType = $scope->getType($expr->var);
            if (!$calledOnType instanceof TypeWithClassName) {
                throw new ConstExprEvaluationException();
            }
            $className = $calledOnType->getClassName();
            $methodName = (string)$expr->name;
            return $this->methodCallPlaceholder($className, $methodName);
        } elseif ($expr instanceof StaticCall) {
            if (!$expr->name instanceof Identifier) {
                throw new ConstExprEvaluationException();
            }
            if (!$expr->class instanceof Name) {
                throw new ConstExprEvaluationException();
            }
            $className = $scope->resolveName($expr->class);
            $methodName = (string)$expr->name;
            return $this->methodCallPlaceholder($className, $methodName);
        }

        throw new ConstExprEvaluationException();
    }

    private function methodCallPlaceholder(string $className, string $methodName): string
    {
        return self::METHOD_START . $className . self::METHOD_SEPARATOR . $methodName . self::METHOD_END;
    }

    /**
     * @param ?string $path
     * @return ?array<?string>
     */
    public function expand(?string $path, MethodFinder $methodFinder): ?array
    {
        if ($path === null) {
            return [null];
        }

        // expand method call
        if (strpos($path, self::METHOD_START) !== false) {
            $methodPlaceholderPattern = '#' . self::METHOD_START . '([a-zA-Z0-9_\\\\]*)' . self::METHOD_SEPARATOR . '([a-zA-Z0-9_\\\\]*)' . self::METHOD_END . '#';
            $matches = [];
            $matched = preg_match($methodPlaceholderPattern, $path, $matches);
            if (!$matched) {
                throw new ShouldNotHappenException('Invalid method call placeholder in path "' . $path . '"');
            }
            $methodPlaceholder = $matches[0];
            $className = $matches[1];
            $methodName = $matches[2];
            $returnType = $methodFinder->find($className, $methodName)->getReturnType();
            $options = $returnType !== null ? $returnType->getConstantStrings() : [];
            if (count($options) === 0) {
                if (!$this->resolveAllPossiblePaths) {
                    return [null];
                }
                $options = [new ConstantStringType('*')];
            }
            $expandedPaths = [];
            foreach ($options as $option) {
                $replacedPath = preg_replace('#' . preg_quote($methodPlaceholder, '#') . '#', $option->getValue(), $path, 1);
                $expandedPaths[] = $this->expand($replacedPath, $methodFinder) ?? [];
            }
            return array_merge(...$expandedPaths);
        }

        if ($path === '*') {
            return null;
        }

        // expand wildcard
        if (strpos($path, '*') !== false) {
            $dirWithoutWildcards = (string)Strings::before((string)Strings::before($path, '*'), '/', -1);
            $pattern = substr($path, strlen($dirWithoutWildcards) + 1);

            $paths = [];
            /** @var SplFileInfo $file */
            foreach (Finder::findFiles($pattern)->from($dirWithoutWildcards) as $file) {
                $paths[] = (string)$file;
            }
            return count($paths) > 0 ? $paths : null;
        }

        return [$path];
    }
}
