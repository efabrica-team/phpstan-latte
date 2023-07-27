<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Template\Form;

use Efabrica\PHPStanLatte\Template\NameTypeItem;
use JsonSerializable;
use PHPStan\Type\Type;
use PHPStan\Type\VerbosityLevel;
use ReturnTypeWillChange;

final class Field implements NameTypeItem, ControlInterface, JsonSerializable
{
    private string $name;

    private Type $type;

    /**
     * @var ?array<int|string, int|string>
     */
    private ?array $options;

    /**
     * @param ?array<int|string, int|string> $options - we don't care about values, so we use only keys as keys and also as values here
     */
    public function __construct(string $name, Type $type, ?array $options = null)
    {
        $this->name = $name;
        $this->type = $type;
        $this->options = $options;
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

    public function withType(Type $type): self
    {
        $clone = clone $this;
        $clone->type = $type;
        return $clone;
    }

    /**
     * @return ?array<int|string, int|string>
     */
    public function getOptions(): ?array
    {
        return $this->options;
    }

    #[ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return [
            'name' => $this->name,
            'type' => $this->getTypeAsString(),
            'options' => $this->options,
        ];
    }
}
