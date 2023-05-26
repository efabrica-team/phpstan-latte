<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Tests\Rule\LatteTemplatesRule\PresenterWithoutModule\Source;

use Efabrica\PHPStanLatte\Tests\Rule\LatteTemplatesRule\PresenterWithoutModule\Fixtures\ComponentsPresenter;
use Nette\Application\UI\Presenter;

final class ControlRegistrator
{
    public function register(Presenter $presenter)
    {
        if ($presenter instanceof ComponentsPresenter) {
            $presenter->addComponent(new SomeControl(), 'someControl');
        } else {
            $presenter->addComponent(new SomeControl(), 'someControl');
        }

        if ($presenter instanceof ComponentsPresenter) {
            $presenter->addComponent(new SomeControl(), 'someUnionControl');
        } else {
            $presenter->addComponent(new SomeBodyControl(), 'someUnionControl');
        }
    }
}
