<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Tests\Rule\LatteTemplatesRule\SimpleControl\Fixtures\Links;

use Nette\Application\UI\Control;

final class SomeControl extends Control
{
    public function render(): void
    {
        $this->template->render(__DIR__ . '/default.latte');
    }

    public function handleDelete(int $id): void
    {
    }
}
