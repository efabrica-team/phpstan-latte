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

final class Filter implements NameTypeItem, JsonSerializable
{
    private string $name;

    private Type $type;

    public function __construct(string $name, Type $type)
    {
        $this->name = $name;
        $this->type = TypeHelper::resolveType($type);
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

    public function getSignatureHash(): string
    {
        return md5((string)json_encode([
          'name' => $this->name,
          'type' => $this->type->describe(VerbosityLevel::precise()),
        ]));
    }

    #[ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return [
            'name' => $this->name,
            'type' => TypeHelper::serializeType($this->type),
        ];
    }

    /**
     * @param array{name: string, type: string} $data
     */
    public static function fromJson(array $data, TypeStringResolver $typeStringResolver): self
    {
        return new self(
            $data['name'],
            $typeStringResolver->resolve($data['type'])
        );
    }
}
