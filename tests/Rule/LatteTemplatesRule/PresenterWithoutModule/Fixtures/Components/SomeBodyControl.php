<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Tests\Rule\LatteTemplatesRule\PresenterWithoutModule\Fixtures\Components;

use Nette\Application\UI\Control;

// COLLECT: NODE NetteApplicationUIControl {"className":"SomeBodyControl"}
final class SomeBodyControl extends Control
{
    public function render(): void
    {
        // COLLECT: TEMPLATE control.latte SomeBodyControl::render ["presenter","control"] ["table"]
        $this->template->render(__DIR__ . '/control.latte');
    }

    protected function createComponentTable(): SomeTableControl
    {
        return new SomeTableControl();
    }
}
