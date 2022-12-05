<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Template;

use JsonSerializable;
use PHPStan\ShouldNotHappenException;
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
        return $this->type->describe(VerbosityLevel::precise());
    }

    /**
     * @return array{name: string, type: string}
     */
    public function toArray(): array
    {
        return [
          'name' => $this->name,
          'type' => serialize($this->type),
        ];
    }

    /**
     * @param array{name: string, type: string} $item
     */
    public static function fromArray(array $item): self
    {
        $type = unserialize($item['type']);
        if (!$type instanceof Type) {
            throw new ShouldNotHappenException('Cannot unserialize variable type');
        }
        return new Variable($item['name'], $type);
    }

    #[ReturnTypeWillChange]
    public function jsonSerialize()
    {
        $this->toArray();
    }
}
