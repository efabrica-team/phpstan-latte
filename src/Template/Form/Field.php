<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Template\Form;

use Efabrica\PHPStanLatte\Template\NameTypeItem;
use Efabrica\PHPStanLatte\Type\TypeHelper;
use PHPStan\Analyser\NameScope;
use PHPStan\PhpDoc\TypeStringResolver;
use PHPStan\PhpDocParser\Printer\Printer;
use PHPStan\Type\Type;
use ReturnTypeWillChange;

final class Field implements NameTypeItem, ControlInterface
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
        $this->type = TypeHelper::resolveType($type);
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
        return (new Printer())->print($this->type->toPhpDocNode());
    }

    public function withType(Type $type): self
    {
        $clone = clone $this;
        $clone->type = TypeHelper::resolveType($type);
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
            'class' => self::class,
            'name' => $this->name,
            'type' => TypeHelper::serializeType($this->type),
            'options' => $this->options,
        ];
    }

    public static function fromJson(array $data, TypeStringResolver $typeStringResolver): self
    {
        return new self(
            $data['name'],
            $typeStringResolver->resolve($data['type']),
            $data['options'] ?? null
        );
    }
}
