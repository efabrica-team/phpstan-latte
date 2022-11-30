<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Tests\Rule\LatteTemplatesRule\SimpleControl\Fixtures\ResolveError;

use Nette\Application\UI\Control;

final class SomeControl extends Control
{
    public function render(): void
    {
    }

    public function renderNotEvaluated(string $param): void
    {
        $this->template->render(__DIR__ . $param . '.latte');
    }

    public function renderNotEvaluatedVar(string $param): void
    {
        $var = __DIR__ . $param . '.latte';
        $this->template->render($var);
    }
}
