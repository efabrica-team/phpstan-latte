<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Template;

use JsonSerializable;
use PHPStan\Type\Type;
use PHPStan\Type\VerbosityLevel;
use ReturnTypeWillChange;

final class Component implements NameTypeItem, JsonSerializable
{
    private string $name;

    private Type $type;

    /** @var Component[] */
    private array $subcomponents = [];

    public function __construct(string $name, Type $type)
    {
        $this->name = $name;
        $this->type = $type;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getType(): Type
    {
        return $this->type;
    }

    public function getTypeAsString(): string
    {
        return $this->type->describe(VerbosityLevel::typeOnly());
    }

    /**
     * @param Component[] $subcomponents
     */
    public function addSubcomponents(array $subcomponents): void
    {
        $this->subcomponents = ItemCombinator::merge($this->subcomponents, $subcomponents);
    }

    /**
     * @return Component[]
     */
    public function getSubcomponents(): array
    {
        return $this->subcomponents;
    }

    public function withType(Type $type): self
    {
        $clone = clone $this;
        $clone->type = $type;
        return $clone;
    }

    #[ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return [
          'name' => $this->name,
          'type' => $this->getTypeAsString(),
        ];
    }
}
