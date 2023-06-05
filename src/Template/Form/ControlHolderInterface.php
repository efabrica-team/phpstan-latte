<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Template\Form;

interface ControlHolderInterface
{
    /**
     * @return ControlInterface[]
     */
    public function getControls(): array;

    public function getControl(string $name): ?ControlInterface;
}
