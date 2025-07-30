<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Template\Form;

use Efabrica\PHPStanLatte\Template\Form\Behavior\ControlHolderBehavior;
use Efabrica\PHPStanLatte\Template\ItemCombinator;
use Efabrica\PHPStanLatte\Template\NameTypeItem;
use Efabrica\PHPStanLatte\Type\TypeHelper;
use InvalidArgumentException;
use JsonSerializable;
use PHPStan\Analyser\NameScope;
use PHPStan\PhpDoc\TypeStringResolver;
use PHPStan\PhpDocParser\Printer\Printer;
use PHPStan\Type\Type;
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
        $this->type = TypeHelper::resolveType($type);
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
        return (new Printer())->print($this->type->toPhpDocNode());
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
            ItemCombinator::union($this->controls, $controls),
            $this->groups
        );
    }

    /**
     * @param Group[] $groups
     */
    public function withGroups(array $groups): self
    {
        return new self(
            $this->name,
            $this->type,
            $this->controls,
            ItemCombinator::merge($this->groups, $groups)
        );
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
            'name' => $this->name,
            'type' => TypeHelper::serializeType($this->type),
            'controls' => array_map(fn(ControlInterface $control) => $control->jsonSerialize(), $this->controls),
            'groups' => array_map(fn(Group $group) => $group->jsonSerialize(), $this->groups),
        ];
    }

    public static function fromJson(array $data, TypeStringResolver $typeStringResolver): self
    {
        $controls = [];
        foreach ($data['controls'] as $controlData) {
            $controls[] = self::controlFromJson($controlData, $typeStringResolver);
        }

        $groups = [];
        foreach ($data['groups'] as $groupData) {
            $groups[] = Group::fromJson($groupData, $typeStringResolver);
        }

        return new self(
            $data['name'],
            $typeStringResolver->resolve($data['type']),
            $controls,
            $groups
        );
    }

    public static function controlFromJson(array $controlData, TypeStringResolver $typeStringResolver): ControlInterface
    {
        switch ($controlData['class']) {
            case Container::class:
                return Container::fromJson($controlData, $typeStringResolver);
            case Field::class:
                return Field::fromJson($controlData, $typeStringResolver);
            default:
                throw new InvalidArgumentException(sprintf('Unknown control type: %s', $controlData['class']));
        }
    }
}
