<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Template\Form;

use Efabrica\PHPStanLatte\Template\Form\Behavior\ControlHolderBehavior;
use JsonSerializable;
use PHPStan\Type\Type;
use PHPStan\Type\VerbosityLevel;
use ReturnTypeWillChange;

final class Container implements ControlHolderInterface, ControlInterface, JsonSerializable
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
        $this->type = $type;
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
        return $this->type->describe(VerbosityLevel::typeOnly());
    }

    #[ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return [
            'name' => $this->name,
            'type' => $this->getTypeAsString(),
            'controls' => $this->controls,
        ];
    }
}
