<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Tests\Rule\LatteTemplatesRule\SimpleControl\Fixtures\TraitOverriding;

use Nette\Application\UI\Control;

abstract class BaseControl extends Control
{
    protected function getTemplatePath(): string
    {
        return __DIR__ . '/base.latte';
    }
}
