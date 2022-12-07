<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Tests\Rule\LatteTemplatesRule\SimpleControl\Fixtures\ResolveError;

use Nette\Application\UI\Control;

// COLLECT: NODE NetteApplicationUIControl {"className":"SomeControl"}
final class SomeControl extends Control
{
    // ERROR: Cannot resolve latte template for SomeControl::render().
    public function render(): void
    {
    }

    public function renderNotEvaluated(string $param): void
    {
        // ERROR: Cannot automatically resolve latte template from expression.
        $this->template->render(__DIR__ . '/' . $param . '.latte');
    }

    public function renderNotEvaluatedVar(string $param): void
    {
        $var = __DIR__ . $param . '.latte';
        // ERROR: Cannot automatically resolve latte template from expression.
        $this->template->render($var);
    }
}
