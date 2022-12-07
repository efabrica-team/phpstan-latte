<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Tests\Rule\LatteTemplatesRule\SimpleControl\Fixtures\ThisTemplate;

use Nette\Application\UI\Control;

// COLLECT: NODE NetteApplicationUIControl {"className":"SomeControl"}
final class SomeControl extends Control
{
    public function render(): void
    {
        $this->template->a = null;
        $this->template->a = 'a';
        $this->template->b = 'b';

        $this->template->setFile(__DIR__ . '/default.latte');
        // COLLECT: TEMPLATE default.latte SomeControl::render ["presenter","control","a","b"] []
        $this->template->render();
    }
}
