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
        $this->recursion(5);
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

    public function actionEmpty(): void
    {
    }

    public function actionRedirect(): void
    {
        $this->redirect('default');
    }

    public function actionDirectRender(): void
    {
        $this->template->fromTemplate = 'a';
        $this->template->render(__DIR__ . '/templates/Variables/direct.latte', ['fromRender' => 'b']);
        $this->terminate();
    }

    private function bar(): void
    {
        $this->template->variableFromOtherMethod = 'bar';
    }

    private function recursion(int $counter): void
    {
        $this->template->variableFromRecursionMethod = 'bar';
        if ($counter > 0) {
            $this->recursion($counter - 1);
        }
    }
}
