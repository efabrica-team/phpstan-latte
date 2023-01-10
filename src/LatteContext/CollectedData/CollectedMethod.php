<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\LatteContext\CollectedData;

use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;

final class CollectedMethod extends CollectedLatteContextObject
{
    private string $className;

    private string $methodName;

    private bool $alwaysTerminated;

    private ?Type $returnType;

    public function __construct(string $className, string $methodName, bool $alwaysTerminated, ?Type $returnType = null)
    {
        $this->className = $className;
        $this->methodName = $methodName;
        $this->alwaysTerminated = $alwaysTerminated;
        $this->returnType = $returnType;
    }

    public function getClassName(): string
    {
        return $this->className;
    }

    public function getMethodName(): string
    {
        return $this->methodName;
    }

    public function isAlwaysTerminated(): bool
    {
        return $this->alwaysTerminated;
    }

    public function getReturnType(): ?Type
    {
        return $this->returnType;
    }

    public static function unknown(string $className, string $methodName): self
    {
        return new CollectedMethod($className, $methodName, false);
    }

    public static function combine(string $className, string $methodName, CollectedMethod ...$collectedMethods): self
    {
        if (count($collectedMethods) === 0) {
            return self::unknown($className, $methodName);
        }

        $alwaysTerminated = false;
        $returnTypes = [];
        foreach ($collectedMethods as $collectedMethod) {
            $alwaysTerminated = $alwaysTerminated || $collectedMethod->isAlwaysTerminated();
            $returnTypes[] = $collectedMethod->getReturnType();
        }
        $returnTypes = array_filter($returnTypes);

        return new self($className, $methodName, $alwaysTerminated, $returnTypes ? TypeCombinator::union(...$returnTypes) : null);
    }
}
