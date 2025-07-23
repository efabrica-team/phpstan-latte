<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Tests\Rule\LatteTemplatesRule\SimpleControl\Fixtures\Enums;

use Efabrica\PHPStanLatte\Tests\Rule\LatteTemplatesRule\SimpleControl\Source\EnumSomething;
use Nette\Application\UI\Control;

final class SomeControl extends Control
{
    public function renderDefault(): void
    {
        $this->template->enum = EnumSomething::Foo;
        $this->template->setFile(__DIR__ . '/default.latte');
        $this->template->render();
    }
}
