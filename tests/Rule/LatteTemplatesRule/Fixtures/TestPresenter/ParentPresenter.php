<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Tests\Rule\LatteTemplatesRule\Fixtures\TestPresenter;

use Nette\Application\UI\Presenter;

class ParentPresenter extends Presenter
{
    protected function baz(): void
    {
        $this->template->variableFromParent = 'baz';
    }
}
