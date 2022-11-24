<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Template;

use JsonSerializable;
use PHPStan\Type\Type;
use PHPStan\Type\VerbosityLevel;

final class Variable implements JsonSerializable
{
    private string $name;

    private Type $type;

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
     * @return array<string, string>
     */
    public function jsonSerialize(): array
    {
        return [
          'name' => $this->name,
          'type' => $this->getTypeAsString(),
        ];
    }
}
