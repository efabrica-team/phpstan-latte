<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Collector\ValueObject;

use Efabrica\PHPStanLatte\Template\Component;
use JsonSerializable;
use ReturnTypeWillChange;

final class CollectedComponent implements JsonSerializable
{
    private string $className;

    private string $methodName;

    private Component $component;

    public function __construct(string $className, string $methodName, Component $component)
    {
        $this->className = $className;
        $this->methodName = $methodName;
        $this->component = $component;
    }

    public function getClassName(): string
    {
        return $this->className;
    }

    public function getMethodName(): string
    {
        return $this->methodName;
    }

    public function getComponent(): Component
    {
        return $this->component;
    }

    #[ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return [
            'className' => $this->className,
            'methodName' => $this->methodName,
            'component' => $this->component,
        ];
    }
}
