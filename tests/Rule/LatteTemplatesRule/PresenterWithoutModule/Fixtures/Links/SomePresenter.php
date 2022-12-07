<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Tests\Rule\LatteTemplatesRule\PresenterWithoutModule\Fixtures\Links;

use Efabrica\PHPStanLatte\Tests\Rule\LatteTemplatesRule\PresenterWithoutModule\Fixtures\ParentPresenter;

// COLLECT: NODE NetteApplicationUIPresenter {"className":"SomePresenter"}
// COLLECT: NODE NetteApplicationUIPresenterStandalone {"className":"SomePresenter"}
// COLLECT: TEMPLATE parent.latte SomePresenter::parent ["presenter","control","variableFromParentAction"] ["parentForm","parentDefaultForm"]
final class SomePresenter extends ParentPresenter
{
    // COLLECT: TEMPLATE default.latte SomePresenter::default ["presenter","control"] ["parentForm"]
    public function actionDefault(): void
    {
    }

    // COLLECT: TEMPLATE create.latte SomePresenter::create ["presenter","control"] ["parentForm"]
    public function actionCreate(): void
    {
    }

    // COLLECT: TEMPLATE edit.latte SomePresenter::edit ["presenter","control"] ["parentForm"]
    public function actionEdit(string $id, int $sorting = 100): void
    {
    }

    // COLLECT: TEMPLATE publish.latte SomePresenter::publish ["presenter","control"] ["parentForm"]
    public function actionPublish(string $id, int $sorting = 100, bool $isActive = true): void
    {
    }

    // COLLECT: TEMPLATE paramsMismatch.latte SomePresenter::paramsMismatch ["presenter","control"] ["parentForm"]
    public function actionParamsMismatch(string $param1)
    {
    }

    // COLLECT: TEMPLATE arrayParam.latte SomePresenter::arrayParam ["presenter","control"] ["parentForm"]
    public function renderParamsMismatch(string $param1, string $param2)
    {
    }

    public function actionArrayParam(array $ids, bool $option = false): void
    {
    }
}
