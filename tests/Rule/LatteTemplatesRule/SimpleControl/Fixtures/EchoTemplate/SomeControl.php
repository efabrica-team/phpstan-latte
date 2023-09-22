<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Tests\Rule\LatteTemplatesRule\SimpleControl\Fixtures\EchoTemplate;

use Nette\Application\UI\Control;

final class SomeControl extends Control
{
    public function render(): void
    {
        $this->template->a = null;
        $this->template->a = 'a';
        $this->template->b = 'b';

        $this->template->setFile(__DIR__ . '/default.latte');
        echo $this->template;
    }
}
