<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Tests\Rule\LatteTemplatesRule\Annotations\Fixtures\IgnoredClass;

use Latte\Runtime\Template;
use Nette\Application\UI\Control;

/**
 * @phpstan-latte-ignore
 */
final class IgnoredControl extends Control
{
    public function render()
    {
        $this->template->render(__DIR__ . '/error.latte');
    }

    public function setVariables(Template $template)
    {
        $template->ignoredVar = 'ignoredVar';
    }
}
