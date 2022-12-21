<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\LatteContext\CollectedData;

final class CollectedMethodCall extends CollectedLatteContextObject
{
    public const CALL = 'call';
    public const TERMINATING_CALL = 'terminating';
    public const OUTPUT_CALL = 'output';

    private string $callerClassName;

    private string $callerMethodName;

    private string $calledClassName;

    private string $calledMethodName;

    private string $type;

    public function __construct(string $callerClassName, string $callerMethodName, string $calledClassName, string $calledMethodName, string $type = self::CALL)
    {
        $this->callerClassName = $callerClassName;
        $this->callerMethodName = $callerMethodName;
        $this->calledClassName = $calledClassName;
        $this->calledMethodName = $calledMethodName;
        $this->type = $type;
    }

    public function getCallerClassName(): string
    {
        return $this->callerClassName;
    }

    public function getCallerMethodName(): string
    {
        return $this->callerMethodName;
    }

    public function getCalledClassName(): string
    {
        return $this->calledClassName;
    }

    public function getCalledMethodName(): string
    {
        return $this->calledMethodName;
    }

    public function isCall(): bool
    {
        return $this->type === self::CALL;
    }

    public function isTerminatingCall(): bool
    {
        return $this->type === self::TERMINATING_CALL;
    }

    public function isOutputCall(): bool
    {
        return $this->type === self::OUTPUT_CALL;
    }
}
