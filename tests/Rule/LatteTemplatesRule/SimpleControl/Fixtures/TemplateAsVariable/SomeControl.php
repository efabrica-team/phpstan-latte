<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Tests\Rule\LatteTemplatesRule\SimpleControl\Fixtures\TemplateAsVariable;

use Nette\Application\UI\Control;

final class SomeControl extends Control
{
    public function render(): void
    {
        $template = $this->template;

        $template->a = null;
        $template->a = 'a';
        $template->b = 'b';

        $template->setFile(__DIR__ . '/default.latte');
        $template->render();
    }
}
