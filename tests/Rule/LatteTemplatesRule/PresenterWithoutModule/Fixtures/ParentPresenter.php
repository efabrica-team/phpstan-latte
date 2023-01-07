<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Tests\Rule\LatteTemplatesRule\PresenterWithoutModule\Fixtures;

use Nette\Application\UI\Form;
use Nette\Application\UI\Presenter;

abstract class ParentPresenter extends Presenter
{
    protected function startup()
    {
        parent::startup();
        $this->template->addFilter('parentStartupFilter', function (string $string): string {
            return uniqid($string);
        });
        $this->template->startupParent = 'startupParent';
    }

    public function actionDefault(): void
    {
        $this->template->variableFromParentCalledViaParent = 'barbaz';
        $this->addComponent(new Form(), 'onlyParentDefaultForm');
    }

    public function actionParent(): void
    {
        $this->template->variableFromParentAction = 'barbaz';
        $this->addComponent(new Form(), 'parentDefaultForm');
        $this->bar();
    }

    protected function bar(): void
    {
    }

    protected function baz(): void
    {
        $this->template->variableFromParent = 'baz';
        $var = 'xxx';
        $this->template->varFromVariable = $var;
    }

    protected function overwritten(): void
    {
        $this->template->overwritted = 'overwritted';
    }

    protected function overwrittenThroughtParent(): void
    {
        $this->parentOverwritten();
    }

    protected function parentOverwritten(): void
    {
        $this->template->parentOverwritted = 'overwritted';
    }

    protected function calledParentOverwritten(): void
    {
        $this->template->calledParentOverwritted = 'overwritted';
        $this->calledParentSecondOverwritten();
    }

    protected function calledParentSecondOverwritten(): void
    {
        $this->template->calledParentSecondOverwritted = 'overwritted';
    }

    protected function createComponentParentForm(): Form
    {
        $form = new Form();
        $form->addText('bar', 'Bar');
        $form->addSubmit('submit');
        $form->onSuccess[] = function (Form $form, array $values): void {
        };
        return $form;
    }

    protected function parentCreateIndirectForm(): Form
    {
        return $this->createIndirectForm();
    }

    // overwritten in FormsPresenter
    protected function createIndirectForm(): Form
    {
        return new Form();
    }
}
