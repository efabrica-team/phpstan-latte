<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Tests\Rule\LatteTemplatesRule\PresenterWithoutModule\Source;

use Nette\Application\UI\Control;

/**
 * @template T
 */
final class SomeTableControl extends Control
{
    public function render(): void
    {
        $this->template->render(__DIR__ . '/control.latte');
    }
}
