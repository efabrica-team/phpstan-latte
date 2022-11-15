<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Tests\Rule\LatteTemplatesRule\Fixtures\TestPresenter;

use Nette\Application\UI\Form;
use Nette\Application\UI\Presenter;

final class FooPresenter extends Presenter
{
    public function actionDefault(): void
    {
        $this->template->foo = 'bar';
    }

    public function actionCreate(): void
    {
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
