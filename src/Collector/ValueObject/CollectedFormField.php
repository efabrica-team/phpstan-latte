<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Collector\ValueObject;

use Efabrica\PHPStanLatte\Type\TypeSerializer;
use PHPStan\Type\Type;
use PHPStan\Type\VerbosityLevel;

/**
 * @phpstan-type CollectedFormFieldArray array{name: string, type: array<string, string>}
 */
final class CollectedFormField extends CollectedValueObject
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
     * @phpstan-return CollectedFormFieldArray
     */
    public function toArray(TypeSerializer $typeSerializer): array
    {
        return [
            'name' => $this->name,
            'type' => $typeSerializer->toArray($this->type),
        ];
    }

    /**
     * @phpstan-param CollectedFormFieldArray $item
     */
    public static function fromArray(array $item, TypeSerializer $typeSerializer): self
    {
        return new CollectedFormField($item['name'], $typeSerializer->fromArray($item['type']));
    }
}
