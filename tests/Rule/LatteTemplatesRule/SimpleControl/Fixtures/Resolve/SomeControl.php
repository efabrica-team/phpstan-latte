<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Tests\Rule\LatteTemplatesRule\SimpleControl\Fixtures\Resolve;

use Nette\Application\UI\Control;

final class SomeControl extends Control
{
    public function renderConstVar(): void
    {
        $var = '/default.latte';
        $this->template->render(__DIR__ . $var);
    }

    public function renderConstVar2(): void
    {
        $var = __DIR__ . '/default2.latte';
        $this->template->render($var);
    }

    public function renderConstVarIf(bool $param): void
    {
        if ($param) {
            $var = __DIR__ . '/default.latte';
        } else {
            $var = __DIR__ . '/other.latte';
        }
        $this->template->render($var);
    }
}
