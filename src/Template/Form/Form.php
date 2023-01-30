<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Template\Form;

use Efabrica\PHPStanLatte\Template\Form\Behavior\ControlHolderBehavior;
use Efabrica\PHPStanLatte\Template\ItemCombinator;
use Efabrica\PHPStanLatte\Template\NameTypeItem;
use JsonSerializable;
use PHPStan\Type\Type;
use PHPStan\Type\VerbosityLevel;
use ReturnTypeWillChange;

final class Form implements NameTypeItem, ControlHolderInterface, JsonSerializable
{
    use ControlHolderBehavior;

    private string $name;

    private Type $type;

    /** @var Group[] */
    private array $groups;

    /**
     * @param ControlInterface[] $controls
     * @param Group[] $groups
     */
    public function __construct(string $name, Type $type, array $controls = [], array $groups = [])
    {
        $this->name = $name;
        $this->type = $type;
        $this->addControls($controls);
        $this->groups = $groups;
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
     * @return Group[]
     */
    public function getGroups(): array
    {
        return $this->groups;
    }

    public function getGroup(string $name): ?Group
    {
        return $this->groups[$name] ?? null;
    }

    /**
     * @param ControlInterface[] $controls
     */
    public function withControls(array $controls): self
    {
        return new self(
            $this->name,
            $this->type,
            ItemCombinator::union($this->controls, $controls)
        );
    }

    public function withType(Type $type): self
    {
        $clone = clone $this;
        $clone->type = $type;
        return $clone;
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
