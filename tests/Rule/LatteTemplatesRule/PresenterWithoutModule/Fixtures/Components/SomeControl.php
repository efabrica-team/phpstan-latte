<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Tests\Rule\LatteTemplatesRule\PresenterWithoutModule\Fixtures\Components;

use Nette\Application\UI\Control;

// COLLECT: NODE NetteApplicationUIControl {"className":"SomeControl"}
final class SomeControl extends Control
{
    public function __construct()
    {
        $this['header'] = new SomeHeaderControl();
    }

    public function render(): void
    {
        // COLLECT: TEMPLATE SomeControl.latte SomeControl::render ["presenter","control"] ["body","footer","header"]
        $this->template->render(__DIR__ . '/SomeControl.latte');
    }

    public function renderOtherRender(): void
    {
        // COLLECT: TEMPLATE SomeControl.latte SomeControl::renderOtherRender ["presenter","control"] ["body","footer","header"]
        $this->render();
    }

    public function renderAnotherRender(): void
    {
        // COLLECT: TEMPLATE SomeControl.latte SomeControl::renderAnotherRender ["presenter","control"] ["body","footer","header"]
        $this->template->render(__DIR__ . '/SomeControl.latte');
    }

    protected function createComponentBody(): SomeBodyControl
    {
        return new SomeBodyControl();
    }

    protected function createComponentFooter(): SomeFooterControl
    {
        return new SomeFooterControl();
    }
}
