<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Tests\Rule\LatteTemplatesRule\PresenterWithoutModule\Fixtures\Components;

use Nette\Application\UI\Control;

// COLLECT: NODE NetteApplicationUIControl {"className":"SomeHeaderControl"}
final class SomeHeaderControl extends Control
{
    public function render(): void
    {
        // COLLECT: TEMPLATE control.latte SomeHeaderControl::render ["presenter","control"] []
        $this->template->render(__DIR__ . '/control.latte');
    }
}
