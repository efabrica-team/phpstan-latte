<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Template;

use Efabrica\PHPStanLatte\Type\TypeHelper;
use JsonSerializable;
use PHPStan\PhpDoc\TypeStringResolver;
use PHPStan\PhpDocParser\Printer\Printer;
use PHPStan\Type\Type;
use PHPStan\Type\VerbosityLevel;
use ReturnTypeWillChange;

final class Component implements NameTypeItem, JsonSerializable
{
    private string $name;

    private Type $type;

    /** @var Component[] */
    private array $subcomponents = [];

    /**
     * @param Component[] $subcomponents
     */
    public function __construct(string $name, Type $type, array $subcomponents = [])
    {
        $this->name = $name;
        $this->type = TypeHelper::resolveType($type);
        $this->subcomponents = $subcomponents;
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
        $clone->type = TypeHelper::resolveType($type);
        return $clone;
    }

    public function getSignatureHash(): string
    {
        return md5((string)json_encode([
          'name' => $this->name,
          'type' => $this->type->describe(VerbosityLevel::precise()),
          'subcomponents' => array_map(
              static fn(Component $component): string => $component->getSignatureHash(),
              $this->subcomponents
          ),
        ]));
    }

    /**
     * @return array<string, mixed>
     */
    #[ReturnTypeWillChange]
    public function jsonSerialize(): array
    {
        return [
          'name' => $this->name,
          'type' => TypeHelper::serializeType($this->type),
          'subcomponents' => array_map(
              static fn(Component $component): array => $component->jsonSerialize(),
              $this->subcomponents
          ),
        ];
    }

    /**
     * @param array{ name: string, type: string, subcomponents?: array<int, array<string, mixed>> } $data
     */
    public static function fromJson(array $data, TypeStringResolver $typeStringResolver): self
    {
        return new self(
            $data['name'],
            $typeStringResolver->resolve($data['type']),
            array_map(
                static fn(array $componentData): Component => Component::fromJson($componentData, $typeStringResolver),
                $data['subcomponents'] ?? []
            )
        );
    }
}
