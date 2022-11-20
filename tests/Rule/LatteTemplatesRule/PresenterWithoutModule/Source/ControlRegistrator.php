<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Tests\Rule\LatteTemplatesRule\PresenterWithoutModule\Source;

use Nette\Application\UI\Presenter;

final class ControlRegistrator
{
    public function register(Presenter $presenter)
    {
        $presenter->addComponent(new SomeControl(), 'someControl');
    }
}
