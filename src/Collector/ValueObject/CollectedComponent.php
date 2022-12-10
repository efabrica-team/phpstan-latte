<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Collector\ValueObject;

use Efabrica\PHPStanLatte\Template\Component;
use Efabrica\PHPStanLatte\Type\TypeSerializer;
use PHPStan\Type\Type;

/**
 * @phpstan-type CollectedComponentArray array{className: string, methodName: string, componentName: string, componentType: array<string, string>}
 */
final class CollectedComponent extends CollectedValueObject
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

    /**
     * @phpstan-return CollectedComponentArray
     */
    public function toArray(TypeSerializer $typeSerializer): array
    {
        return [
            'className' => $this->className,
            'methodName' => $this->methodName,
            'componentName' => $this->getComponentName(),
            'componentType' => $typeSerializer->toArray($this->component->getType()),
        ];
    }

    /**
     * @phpstan-param CollectedComponentArray $item
     */
    public static function fromArray(array $item, TypeSerializer $typeSerializer): self
    {
        $component = new Component($item['componentName'], $typeSerializer->fromArray($item['componentType']));
        return new CollectedComponent($item['className'], $item['methodName'], $component);
    }
}
