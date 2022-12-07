<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Tests\Rule\LatteTemplatesRule\PresenterWithoutModule\Fixtures\Components;

use Efabrica\PHPStanLatte\Tests\Rule\LatteTemplatesRule\PresenterWithoutModule\Fixtures\ParentPresenter;
use Nette\Application\UI\Form;

// COLLECT: NODE NetteApplicationUIPresenter {"className":"SomePresenter"}
// COLLECT: NODE NetteApplicationUIPresenterStandalone {"className":"SomePresenter"}
// COLLECT: TEMPLATE parent.latte SomePresenter::parent ["startupParent","presenter","control","variableFromParentAction"] ["form","noType","parentForm","parentDefaultForm"]
// COLLECT: TEMPLATE noAction.latte SomePresenter:: ["startupParent","presenter","control"] ["form","noType","parentForm"]
final class SomePresenter extends ParentPresenter
{
    /** @inject */
    public ControlRegistrator $controlRegistrator;

    protected function startup()
    {
        parent::startup();
    }

    // COLLECT: TEMPLATE default.latte SomePresenter::default ["startupParent","presenter","control","variableFromParentCalledViaParent"] ["form","noType","parentForm","onlyParentDefaultForm","someControl"]
    public function actionDefault(): void
    {
        parent::actionDefault();
        $this->controlRegistrator->register($this);
    }

    // COLLECT: TEMPLATE create.latte SomePresenter::create ["startupParent","presenter","control"] ["form","noType","parentForm","onlyCreateForm"]
    public function actionCreate(): void
    {
        $form = new Form();
        $this->addComponent($form, 'onlyCreateForm');
    }

    protected function createComponentForm(): Form
    {
        $form = new Form();
        $form->addText('foo', 'Foo');
        $form->addSubmit('submit');
        $form->onSuccess[] = function (Form $form, array $values): void {
        };
        return $form;
    }

    protected function createComponentNoType()
    {
        $form = new Form();
        return $form;
    }
}
