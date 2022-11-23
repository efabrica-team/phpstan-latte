<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Tests\Rule\LatteTemplatesRule\PresenterWithoutModule\Fixtures;

use Efabrica\PHPStanLatte\Tests\Rule\LatteTemplatesRule\PresenterWithoutModule\Source\ControlRegistrator;
use Nette\Application\UI\Form;

final class ComponentsPresenter extends ParentPresenter
{
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
        $this->addComponent(new Form(), 'onlyCreateForm');
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
}
