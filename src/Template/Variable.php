<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Template;

use JsonSerializable;
use PHPStan\Type\Type;
use PHPStan\Type\VerbosityLevel;
use ReturnTypeWillChange;

final class Variable implements NameTypeItem, JsonSerializable
{
    private string $name;

    private Type $type;

    private bool $mightBeUndefined;

    public function __construct(string $name, Type $type, bool $mightBeUndefined = false)
    {
        $this->name = $name;
        $this->type = $type;
        $this->mightBeUndefined = $mightBeUndefined;
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
        return $this->type->describe(VerbosityLevel::precise());
    }

    public function mightBeUndefined(): bool
    {
        return $this->mightBeUndefined;
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
            'undefined' => $this->mightBeUndefined,
        ];
    }
}
