<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Tests\Rule\LatteTemplatesRule\SimpleControl\Fixtures\ThisGetTemplate;

use Nette\Application\UI\Control;

final class SomeControl extends Control
{
    public function render(): void
    {
        $this->getTemplate()->a = null;
        $this->getTemplate()->a = 'a';
        $this->getTemplate()->b = 'b';

        $this->getTemplate()->setFile(__DIR__ . '/default.latte');
        $this->getTemplate()->render();
    }
}
