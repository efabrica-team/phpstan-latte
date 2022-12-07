<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Tests\Rule\LatteTemplatesRule\PresenterWithoutModule\Fixtures\Components;

use Nette\Application\UI\Presenter;

final class ControlRegistrator
{
    public function register(Presenter $presenter)
    {
        $presenter->addComponent(new SomeControl(), 'someControl');
    }
}
