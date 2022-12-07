<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Tests\Rule\LatteTemplatesRule\PresenterWithoutModule\Fixtures\Variables;

use Efabrica\PHPStanLatte\Tests\Rule\LatteTemplatesRule\PresenterWithoutModule\Fixtures\ParentPresenter;

// COLLECT: NODE NetteApplicationUIPresenter {"className":"SomePresenter"}
// COLLECT: NODE NetteApplicationUIPresenterStandalone {"className":"SomePresenter"}
// COLLECT: TEMPLATE parent.latte SomePresenter::parent ["startup","startupParent","presenter","control","variableFromParentAction"] ["parentForm","parentDefaultForm"]
// COLLECT: TEMPLATE noAction.latte SomePresenter:: ["startup","startupParent","presenter","control"] ["parentForm"]
final class SomePresenter extends ParentPresenter
{
    protected function startup()
    {
        parent::startup();
        $this->template->startup = 'startup';
    }

    // COLLECT: TEMPLATE default.latte SomePresenter::default ["startup","startupParent","presenter","control","title","viaGetTemplate","variableFromParentCalledViaParent","variableFromParent","varFromVariable","variableFromOtherMethod","fromRenderDefault"] ["parentForm","onlyParentDefaultForm"]
    public function actionDefault(): void
    {
        parent::actionDefault();
        $this->template->title = 'foo';
        $this->bar();
        $this->baz();
        $this->getTemplate()->viaGetTemplate = 'foobar';
    }

    public function renderDefault(): void
    {
        $this->template->fromRenderDefault = 'from render default';
    }

    // COLLECT: TEMPLATE other.latte SomePresenter::other ["startup","startupParent","presenter","control","fromOtherAction"] ["parentForm"]
    public function actionOther(): void
    {
        $this->template->fromOtherAction = 'from other action';
    }

    private function bar(): void
    {
        $this->template->variableFromOtherMethod = 'bar';
    }

    // COLLECT: TEMPLATE direct.latte SomePresenter::directRender ["startup","startupParent","presenter","control","fromTemplate","fromRender"] ["parentForm"]
    public function actionDirectRender(): void
    {
        $this->template->fromTemplate = 'a';
        $this->template->render(__DIR__ . '/templates/Some/direct.latte', ['fromRender' => 'b']);
        $this->terminate();
    }
}
