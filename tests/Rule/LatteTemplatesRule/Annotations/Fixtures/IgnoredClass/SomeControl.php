<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Tests\Rule\LatteTemplatesRule\Annotations\Fixtures\IgnoredClass;

use Nette\Application\UI\Control;

// COLLECT: NODE NetteApplicationUIControl {"className":"SomeControl"}
final class SomeControl extends Control
{
    public IgnoredControl $ignoredControl;

    public function render()
    {
        $this->ignoredControl->setVariables($this->template);
        // COLLECT: TEMPLATE SomeControl.latte SomeControl::render ["presenter","control"] []
        $this->template->render(__DIR__ . '/SomeControl.latte');
    }

    // ERROR: Cannot resolve latte template for SomeControl::renderIgnoredClass().
    public function renderIgnoredClass()
    {
        $this->ignoredControl->render();
    }
}
