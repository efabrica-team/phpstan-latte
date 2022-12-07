<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Tests\Rule\LatteTemplatesRule\PresenterWithoutModule\Fixtures\Components;

use Nette\Application\UI\Control;

// COLLECT: NODE NetteApplicationUIControl {"className":"SomeTableControl"}
final class SomeTableControl extends Control
{
    public function render(): void
    {
        // COLLECT: TEMPLATE control.latte SomeTableControl::render ["presenter","control"] []
        $this->template->render(__DIR__ . '/control.latte');
    }
}
