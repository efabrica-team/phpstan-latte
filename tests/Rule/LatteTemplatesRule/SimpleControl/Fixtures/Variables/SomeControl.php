<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Tests\Rule\LatteTemplatesRule\SimpleControl\Fixtures\Variables;

use Nette\Application\UI\Control;

final class SomeControl extends Control
{
    public function render(): void
    {
        $this->template->a = 'a';

        $name = 'b';
        $this->template->{$name} = $name;

        $names = ['c', 'd'];
        foreach ($names as $name) {
            $this->template->{$name} = $name;
        }

        $dynamic1 = 'e';
        $dynamic2 = 'f';
        [$this->template->{$dynamic1}, $this->template->{$dynamic2}] = [$dynamic1, $dynamic2];

        $this->template->render(__DIR__ . '/default.latte');
    }

    public function renderForeach(): void
    {
        $this->template->array = ['a'];
        $this->template->render(__DIR__ . '/foreach.latte');
    }
}
