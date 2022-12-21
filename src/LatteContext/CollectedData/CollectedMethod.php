<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\LatteContext\CollectedData;

final class CollectedMethod extends CollectedLatteContextObject
{
    private string $className;

    private string $methodName;

    private bool $alwaysTerminated;

    public function __construct(string $className, string $methodName, bool $alwaysTerminated)
    {
        $this->className = $className;
        $this->methodName = $methodName;
        $this->alwaysTerminated = $alwaysTerminated;
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

    public static function unknown(string $className, string $methodName): self
    {
        return new CollectedMethod($className, $methodName, false);
    }
}
