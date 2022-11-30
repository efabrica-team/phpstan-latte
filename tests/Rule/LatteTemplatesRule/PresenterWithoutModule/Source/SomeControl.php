<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Tests\Rule\LatteTemplatesRule\PresenterWithoutModule\Source;

use Nette\Application\UI\Control;

final class SomeControl extends Control
{
    public function __construct()
    {
        $this['header'] = new SomeHeaderControl();
    }

    public function render(): void
    {
        $this->template->render(__DIR__ . '/SomeControl.latte');
    }

    public function renderOtherRender(): void
    {
        $this->render();
    }

    public function renderAnotherRender(): void
    {
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
