<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Tests\Rule\LatteTemplatesRule\PresenterWithoutModule\Fixtures;

final class RecursionPresenter extends ParentPresenter
{
    public function renderRecursion(): void
    {
        $this->template->counter = 10;
    }

    public function renderIndirectRecursion(): void
    {
        $this->template->counter = 10;
    }
}
