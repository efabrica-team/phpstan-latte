<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Template;

use JsonSerializable;
use PHPStan\PhpDoc\TypeStringResolver;
use PHPStan\Type\Type;
use PHPStan\Type\VerbosityLevel;
use ReturnTypeWillChange;

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
     * @param array{variableName: string, variableType: string} $item
     */
    public static function fromArray(array $item, TypeStringResolver $typeStringResolver): self
    {
        return new Variable($item['variableName'], $typeStringResolver->resolve($item['variableType']));
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
