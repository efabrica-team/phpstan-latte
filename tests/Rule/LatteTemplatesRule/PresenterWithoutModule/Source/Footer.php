<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Tests\Rule\LatteTemplatesRule\PresenterWithoutModule\Source;

use Nette\Application\UI\Control;

final class Footer extends Control
{
    public function render(): void
    {
        echo 'I am footer';
    }
}
