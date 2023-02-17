<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Tests\Rule\LatteTemplatesRule\PresenterWithoutModule\Fixtures;

use Efabrica\PHPStanLatte\Tests\Rule\LatteTemplatesRule\PresenterWithoutModule\Source\ControlRegistrator;
use Nette\Application\UI\Form;
use Nette\Application\UI\Multiplier;

final class ComponentsPresenter extends ParentPresenter
{
    /** @inject */
    public ControlRegistrator $controlRegistrator;

    public $component;

    protected function startup()
    {
        parent::startup();
    }

    public function actionDefault(): void
    {
        parent::actionDefault();
        $this->controlRegistrator->register($this);
        $this->template->varControl = $this->createComponentForm();
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
        return $this->component;
    }

    protected function createComponentImplicitType()
    {
        $form = new Form();
        return $form;
    }

    /**
     * @return Multiplier<Form>
     */
    protected function createComponentMultiplier(): Multiplier
    {
        /** @var Multiplier<Form> $multiplier */
        $multiplier = new Multiplier([$this, 'buildMultiplied']);
        return $multiplier;
    }

    public function buildMultiplied(string $param): Form
    {
        $form = new Form();
        $form->addSubmit('submit', $param);
        return $form;
    }
}
