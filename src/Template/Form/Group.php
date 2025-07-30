<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Template\Form;

use Efabrica\PHPStanLatte\Template\Form\Behavior\ControlHolderBehavior;
use Efabrica\PHPStanLatte\Template\NameItem;
use JsonSerializable;
use PHPStan\PhpDoc\TypeStringResolver;
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
            'controls' => array_map(fn(ControlInterface $control) => $control->jsonSerialize(), $this->controls),
        ];
    }

    public static function fromJson(array $data, TypeStringResolver $typeStringResolver): self
    {
        $controls = [];
        if (isset($data['controls']) && is_array($data['controls'])) {
            foreach ($data['controls'] as $controlData) {
                $controls[] = Form::controlFromJson($controlData, $typeStringResolver);
            }
        }

        return new self($data['name'] ?? '', $controls);
    }
}
