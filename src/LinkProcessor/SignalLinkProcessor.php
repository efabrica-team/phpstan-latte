<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\LinkProcessor;

use PhpParser\Node\Arg;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Stmt\Expression;
use PHPStan\BetterReflection\BetterReflection;

/**
 * not working as expected because in handle methods you don't have to pass parameters if they are same as in actual request
 */
final class SignalLinkProcessor implements LinkProcessorInterface
{
    private LinkParamsProcessor $linkParamsProcessor;

    private ?string $actualClass = null;

    public function __construct(LinkParamsProcessor $linkParamsProcessor)
    {
        $this->linkParamsProcessor = $linkParamsProcessor;
    }

    public function setActualClass(?string $actualClass): void
    {
        $this->actualClass = $actualClass;
    }

    public function check(string $targetName): bool
    {
        return strpos($targetName, '!') !== false;
    }

    /**
     * @param Arg[] $linkParams
     * @param array<string, mixed> $attributes
     * @return Expression[]
     */
    public function createLinkExpressions(string $targetName, array $linkParams, array $attributes): array
    {
        if ($this->actualClass === null) {
            return [];
        }
        $classReflection = (new BetterReflection())->reflector()->reflectClass($this->actualClass);
        $variable = new Variable('control');
        $methodName = 'handle' . ucfirst(substr($targetName, 0, -1));
        if ($this->actualClass !== null && $classReflection->hasMethod($methodName)) {
            $methodLinkParams = $this->linkParamsProcessor->process($this->actualClass, $methodName, $linkParams);
            return [new Expression(new MethodCall($variable, $methodName, $methodLinkParams), $attributes)];
        }
        return [];
    }
}
