<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Tests\Rule\LatteTemplatesRule\PresenterWithoutModule\Source;

use Nette\Application\UI\Control;

final class SomeControl extends Control
{
    public function __construct()
    {
        $this['header'] = new SomeHeaderControl();
        $this['table'] = new SomeTableControl();
        $this['footer'] = new SomeFooterControl();
    }

    public function render(): void
    {
    }

    protected function createComponentBody(): SomeBodyControl
    {
        return new SomeBodyControl();
    }
}
