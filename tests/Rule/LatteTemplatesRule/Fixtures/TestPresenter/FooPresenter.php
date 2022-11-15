<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Tests\Rule\LatteTemplatesRule\Fixtures\TestPresenter;

use Nette\Application\UI\Form;

final class FooPresenter extends ParentPresenter
{
    public function actionDefault(): void
    {
        $this->template->foo = 'foo';
        $this->bar();
        $this->baz();
        parent::actionDefault();
    }

    public function actionCreate(): void
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
