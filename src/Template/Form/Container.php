<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Template\Form;

use Efabrica\PHPStanLatte\Template\Form\Behavior\ControlHolderBehavior;
use Efabrica\PHPStanLatte\Type\TypeHelper;
use PHPStan\PhpDoc\TypeStringResolver;
use PHPStan\PhpDocParser\Printer\Printer;
use PHPStan\Type\Type;
use ReturnTypeWillChange;

final class Container implements ControlHolderInterface, ControlInterface
{
    use ControlHolderBehavior;

    private string $name;

    private Type $type;

    /**
     * @param ControlInterface[] $controls
     */
    public function __construct(string $name, Type $type, array $controls = [])
    {
        $this->name = $name;
        $this->type = TypeHelper::resolveType($type);
        $this->addControls($controls);
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

    #[ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return [
            'class' => self::class,
            'name' => $this->name,
            'type' => TypeHelper::serializeType($this->type),
            'controls' => array_map(fn(ControlInterface $control) => $control->jsonSerialize(), $this->controls),
        ];
    }

    public static function fromJson(array $data, TypeStringResolver $typeStringResolver): self
    {
        $controls = [];
        foreach ($data['controls'] as $controlData) {
            $controls[$controlData['name']] = Form::controlFromJson($controlData, $typeStringResolver);
        }

        return new self(
            $data['name'],
            $typeStringResolver->resolve($data['type']),
            $controls
        );
    }
}
