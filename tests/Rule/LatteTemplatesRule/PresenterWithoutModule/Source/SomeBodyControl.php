<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Tests\Rule\LatteTemplatesRule\PresenterWithoutModule\Source;

use Nette\Application\UI\Control;
use stdClass;

final class SomeBodyControl extends Control
{
    public function render(): void
    {
        $this->template->render(__DIR__ . '/control.latte');
    }

    /**
     * @return SomeTableControl<stdClass>
     */
    protected function createComponentTable(): SomeTableControl
    {
        return new SomeTableControl();
    }
}
