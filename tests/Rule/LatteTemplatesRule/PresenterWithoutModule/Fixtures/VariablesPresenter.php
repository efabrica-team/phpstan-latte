<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Tests\Rule\LatteTemplatesRule\PresenterWithoutModule\Fixtures;

final class VariablesPresenter extends ParentPresenter
{
    protected function startup()
    {
        parent::startup();
        $this->template->startup = 'startup';
    }

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

    public function actionOther(): void
    {
        $this->template->fromOtherAction = 'from other action';
    }

    private function bar(): void
    {
        $this->template->variableFromOtherMethod = 'bar';
    }
}
