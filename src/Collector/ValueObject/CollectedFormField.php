<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Collector\ValueObject;

/**
 * @phpstan-type CollectedFormFieldArray array{name: string, type: string}
 */
final class CollectedFormField extends CollectedValueObject
{
    private string $name;

    private string $type;

    public function __construct(string $name, string $type)
    {
        $this->name = $name;
        $this->type = $type;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @phpstan-return CollectedFormFieldArray
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'type' => $this->type,
        ];
    }

    /**
     * @phpstan-param CollectedFormFieldArray $item
     */
    public static function fromArray(array $item): self
    {
        return new CollectedFormField($item['name'], $item['type']);
    }
}
