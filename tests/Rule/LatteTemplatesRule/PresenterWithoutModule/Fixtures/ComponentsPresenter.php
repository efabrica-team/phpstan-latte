<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Tests\Rule\LatteTemplatesRule\PresenterWithoutModule\Fixtures;

use Efabrica\PHPStanLatte\Tests\Rule\LatteTemplatesRule\PresenterWithoutModule\Source\ControlRegistrator;
use Efabrica\PHPStanLatte\Tests\Rule\LatteTemplatesRule\PresenterWithoutModule\Source\PresenterTrait;
use Nette\Application\UI\Form;

final class ComponentsPresenter extends ParentPresenter
{
    use PresenterTrait;

    /** @inject */
    public ControlRegistrator $controlRegistrator;

    protected function startup()
    {
        parent::startup();
    }

    public function actionDefault(): void
    {
        parent::actionDefault();
        $this->controlRegistrator->register($this);
    }

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
