<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Tests\Rule\LatteTemplatesRule\PresenterWithoutModule\Fixtures\Components;

use Nette\Application\UI\Control;

// COLLECT: NODE NetteApplicationUIControl {"className":"SomeFooterControl"}
final class SomeFooterControl extends Control
{
    public function render(): void
    {
        // COLLECT: TEMPLATE control.latte SomeFooterControl::render ["presenter","control"] []
        $this->template->render(__DIR__ . '/control.latte');
    }
}
