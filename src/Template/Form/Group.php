<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Template\Form;

use Efabrica\PHPStanLatte\Template\Form\Behavior\ControlHolderBehavior;
use Efabrica\PHPStanLatte\Template\NameItem;
use JsonSerializable;
use ReturnTypeWillChange;

final class Group implements NameItem, ControlHolderInterface, JsonSerializable
{
    use ControlHolderBehavior;

    private string $name;

    /**
     * @param ControlInterface[] $controls
     */
    public function __construct(string $name, array $controls = [])
    {
        $this->name = $name;
        $this->addControls($controls);
    }

    public function getName(): string
    {
        return $this->name;
    }

    #[ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return [
            'name' => $this->name,
            'controls' => $this->controls,
        ];
    }
}
