<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Template\Form\Behavior;

use Efabrica\PHPStanLatte\Template\Form\ControlHolderInterface;
use Efabrica\PHPStanLatte\Template\Form\ControlInterface;

trait ControlHolderBehavior
{
    /** @var array<string, ControlInterface> */
    private array $controls = [];

    /**
     * @return ControlInterface[]
     */
    public function getControls(): array
    {
        return $this->controls;
    }

    public function getControl(string $name): ?ControlInterface
    {
        $nameParts = explode('-', $name);
        $controlName = array_shift($nameParts);
        $control = $this->controls[$controlName] ?? null;
        if ($control instanceof ControlHolderInterface && $nameParts !== []) {
            return $control->getControl(implode('-', $nameParts));
        }
        return $control;
    }

    /**
     * @param ControlInterface[] $controls
     */
    public function addControls(array $controls): void
    {
        foreach ($controls as $control) {
            $this->controls[$control->getName()] = $control;
        }
    }
}
