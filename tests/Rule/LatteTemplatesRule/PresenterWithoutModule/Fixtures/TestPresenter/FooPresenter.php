<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Tests\Rule\LatteTemplatesRule\PresenterWithoutModule\Fixtures\TestPresenter;

use Efabrica\PHPStanLatte\Tests\Rule\LatteTemplatesRule\PresenterWithoutModule\Source\ControlRegistrator;
use Nette\Application\UI\Form;

final class FooPresenter extends ParentPresenter
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
        $this->template->foo = 'foo';
        $this->bar();
        $this->baz();
        $this->controlRegistrator->register($this);
        $this->getTemplate()->foobar = 'foobar';
    }

    public function actionCreate(): void
    {
        $this->addComponent(new Form(), 'onlyCreateForm');
    }

    public function actionEdit(string $id, int $sorting = 100): void
    {
    }

    private function bar(): void
    {
        $this->template->variableFromOtherMethod = 'bar';
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
