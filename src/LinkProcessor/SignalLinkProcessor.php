<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\LinkProcessor;

use PhpParser\Node\Arg;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Stmt\Expression;

/**
 * not working as expected because in handle methods you don't have to pass parameters if they are same as in actual request
 */
final class SignalLinkProcessor implements LinkProcessorInterface
{
    public function check(string $targetName): bool
    {
        return substr_compare($targetName, '!', -strlen('!')) === 0;
    }

    /**
     * @param Arg[] $linkParams
     * @param array<string, mixed> $attributes
     * @return Expression[]
     */
    public function createLinkExpressions(string $targetName, array $linkParams, array $attributes): array
    {
        $variable = new Variable('actualClass');
        $methodName = 'handle' . ucfirst(substr($targetName, 0, -1));
        return [new Expression(new MethodCall($variable, $methodName, $linkParams), $attributes)];
    }
}
