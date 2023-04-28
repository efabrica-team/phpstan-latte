<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Tests\Rule\LatteTemplatesRule\SimpleControl\Fixtures\TraitVariables;

trait MiddleControlBehavior
{
    use MiddleControlSubBehavior;

    public function render(): void
    {
        $this->setupData(123);
        parent::render();
    }
}
