<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Tests\Rule\LatteTemplatesRule\PresenterWithoutModule\Fixtures\Recursion;

use Nette\Application\UI\Presenter;

// COLLECT: NODE NetteApplicationUIPresenter {"className":"SomePresenter"}
// COLLECT: NODE NetteApplicationUIPresenterStandalone {"className":"SomePresenter"}
final class SomePresenter extends Presenter
{
    // COLLECT: TEMPLATE recursion.latte SomePresenter::recursion ["presenter","control","counter"] []
    public function renderRecursion(): void
    {
        $this->template->counter = 10;
    }
}
