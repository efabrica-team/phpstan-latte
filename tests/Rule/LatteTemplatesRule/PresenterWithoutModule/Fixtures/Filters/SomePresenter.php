<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Tests\Rule\LatteTemplatesRule\PresenterWithoutModule\Fixtures\Filters;

use Efabrica\PHPStanLatte\Tests\Rule\LatteTemplatesRule\PresenterWithoutModule\Fixtures\ParentPresenter;

// COLLECT: NODE NetteApplicationUIPresenter {"className":"SomePresenter"}
// COLLECT: NODE NetteApplicationUIPresenterStandalone {"className":"SomePresenter"}
// COLLECT: TEMPLATE parent.latte SomePresenter::parent ["presenter","control","variableFromParentAction"] ["parentForm","parentDefaultForm"]
final class SomePresenter extends ParentPresenter
{
    // COLLECT: TEMPLATE default.latte SomePresenter::default ["presenter","control","title"] ["parentForm"]
    public function actionDefault(): void
    {
        $this->template->title = 'foo';
    }
}
