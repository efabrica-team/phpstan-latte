<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Tests\Rule\LatteTemplatesRule\SimpleControl\Fixtures\TraitVariables;

trait MiddleControlSubBehavior
{
    protected function setupData(int $totalItems): void
    {
        $this->template->totalItems = $totalItems;
    }
}
