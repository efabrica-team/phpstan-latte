<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Tests\Rule\LatteTemplatesRule\SimpleControl\Fixtures\TemplateCastToString;

use Nette\Application\UI\Control;
use Nette\Application\UI\Template;

/**
 * @property-read Template $template
 */
final class SomeControl extends Control
{
    public function render(): void
    {
        $this->template->a = null;
        $this->template->a = 'a';
        $this->template->b = 'b';

        $this->template->setFile(__DIR__ . '/default.latte');
        $result = (string)$this->template;
        echo $result;
    }
}
