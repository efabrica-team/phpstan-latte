<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Tests\Rule\LatteTemplatesRule\SimpleControl\Fixtures\TraitVariables;

use Nette\Application\UI\Control;

abstract class BaseControl extends Control
{
    public function render(): void
    {
        $this->template->baseA = 'baseA';
        $this->template->baseB = 'baseB';
        $this->template->setFile(__DIR__ . '/default.latte');
        $this->template->render();
    }
}
