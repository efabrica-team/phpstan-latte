<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Collector\ValueObject;

use JsonSerializable;
use ReturnTypeWillChange;

final class CollectedMethodCall implements JsonSerializable
{
    private string $callerClassName;

    private string $callerMethodName;

    private string $calledClassName;

    private string $calledMethodName;

    public function __construct(string $callerClassName, string $callerMethodName, string $calledClassName, string $calledMethodName)
    {

        $this->callerClassName = $callerClassName;
        $this->callerMethodName = $callerMethodName;
        $this->calledClassName = $calledClassName;
        $this->calledMethodName = $calledMethodName;
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

    #[ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return [
            'callerClassName' => $this->callerClassName,
            'callerMethodName' => $this->callerMethodName,
            'calledClassName' => $this->calledClassName,
            'calledMethodName' => $this->calledMethodName,
        ];
    }
}
