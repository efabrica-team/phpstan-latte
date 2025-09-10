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

final class Variable implements NameTypeItem, JsonSerializable
{
    private string $name;

    private Type $type;

    private bool $mightBeUndefined;

    public function __construct(string $name, Type $type, bool $mightBeUndefined = false)
    {
        $this->name = $name;
        $this->type = TypeHelper::resolveType($type);
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
        return (new Printer())->print($this->type->toPhpDocNode());
    }

    public function mightBeUndefined(): bool
    {
        return $this->mightBeUndefined;
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
            'undefined' => $this->mightBeUndefined,
        ]));
    }

    #[ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return [
            'name' => $this->name,
            'type' => TypeHelper::serializeType($this->type),
            'undefined' => $this->mightBeUndefined,
        ];
    }

    /**
     * @param array{name: string, type?: string, undefined?: bool} $data
     */
    public static function fromJson(array $data, TypeStringResolver $typeStringResolver): self
    {
        $name = $data['name'];
        $type = $typeStringResolver->resolve($data['type'] ?? 'mixed');
        $mightBeUndefined = $data['undefined'] ?? false;
        return new self($name, $type, $mightBeUndefined);
    }
}
