<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Tests\Rule\LatteTemplatesRule\SimpleControl\Fixtures\TraitOverriding;

trait TemplatePathBehavior
{
    protected function getTemplatePath(): string
    {
        return __DIR__ . '/trait.latte';
    }
}
