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
    }

    protected function baz(): void
    {
        $this->template->variableFromParent = 'baz';
        $var = 'xxx';
        $this->template->varFromVariable = $var;
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
}
