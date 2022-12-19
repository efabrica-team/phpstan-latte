<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Collector\ValueObject;

use Efabrica\PHPStanLatte\Template\Component;
use PHPStan\Type\Type;

final class CollectedComponent extends CollectedLatteContextObject
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

    public function getComponentName(): string
    {
        return $this->component->getName();
    }

    public function getComponentType(): Type
    {
        return $this->component->getType();
    }

    public function getComponentTypeAsString(): string
    {
        return $this->component->getTypeAsString();
    }

    public function getComponent(): Component
    {
        return $this->component;
    }
}
