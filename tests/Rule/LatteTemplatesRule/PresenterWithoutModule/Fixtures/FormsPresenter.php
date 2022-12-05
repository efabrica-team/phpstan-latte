<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Tests\Rule\LatteTemplatesRule\PresenterWithoutModule\Fixtures;

use Nette\Application\UI\Form;

final class FormsPresenter extends ParentPresenter
{
    protected function createComponentFirstForm(): Form
    {
        $form = new Form();
        $form->setTranslator(null);
        $form->addText('text', 'Text');
        $form->addTextArea('textarea', 'Textarea')
            ->setHtmlAttribute('class', 'textarea-class');
        $form->addSubmit('submit');

        $form->onSuccess[] = function (Form $form, array $values): void {
        };

        return $form;
    }

    protected function createComponentSecondForm(): Form
    {
        $form = new Form();
        $form->setTranslator(null);
        $form->addSelect('select', 'Select', ['item1', 'item2', 'item3']);
        $form->addCheckbox('checkbox', 'Checkbox');
        $form->addText('username', 'Username');
        $form->addPassword('password', 'Passowrd');

        $form->addSubmit('submit');

        $form->onSuccess[] = function (Form $form, array $values): void {
        };

        return $form;
    }
}
