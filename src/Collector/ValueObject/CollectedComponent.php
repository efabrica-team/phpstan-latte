<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Collector\ValueObject;

use Efabrica\PHPStanLatte\Template\Component;
use PHPStan\PhpDoc\TypeStringResolver;

/**
 * @phpstan-type CollectedComponentArray array{className: string, methodName: string, componentName: string, componentType: string}
 */
final class CollectedComponent
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

    public function getComponentType(): string
    {
        return $this->component->getTypeAsString();
    }

    public function getComponent(): Component
    {
        return $this->component;
    }

    /**
     * @phpstan-return CollectedComponentArray
     */
    public function toArray(): array
    {
        return [
            'className' => $this->className,
            'methodName' => $this->methodName,
            'componentName' => $this->getComponentName(),
            'componentType' => $this->getComponentType(),
        ];
    }

    /**
     * @param CollectedComponentArray $item
     */
    public static function fromArray(array $item, TypeStringResolver $typeStringResolver): self
    {
        $component = new Component($item['componentName'], $typeStringResolver->resolve($item['componentType']));
        return new CollectedComponent($item['className'], $item['methodName'], $component);
    }
}
