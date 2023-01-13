<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Template\Form\Behavior;

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
        return $this->controls[$name] ?? null;
    }

    /**
     * @param ControlInterface[] $controls
     */
    private function addControls(array $controls): void
    {
        foreach ($controls as $control) {
            $this->controls[$control->getName()] = $control;
        }
    }
}
