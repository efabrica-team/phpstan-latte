<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Tests\Rule\LatteTemplatesRule\PresenterWithoutModule\Fixtures\Forms;

use Efabrica\PHPStanLatte\Tests\Rule\LatteTemplatesRule\PresenterWithoutModule\Fixtures\ParentPresenter;
use Nette\Application\UI\Form;

// COLLECT: NODE NetteApplicationUIPresenter {"className":"SomePresenter"}
// COLLECT: NODE NetteApplicationUIPresenterStandalone {"className":"SomePresenter"}
// COLLECT: TEMPLATE parent.latte SomePresenter::parent ["presenter","control","variableFromParentAction"] ["firstForm","secondForm","customForm","parentForm","parentDefaultForm"]
// COLLECT: TEMPLATE default.latte SomePresenter::default ["presenter","control","variableFromParentCalledViaParent"] ["firstForm","secondForm","customForm","parentForm","onlyParentDefaultForm"]
final class SomePresenter extends ParentPresenter
{
    protected function createComponentFirstForm(): Form
    {
        $form = new Form();
        $form->setTranslator(null);
        $form->addText('text', 'Text')
            ->setRequired();
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
        $form->addText('username', 'Username')
            ->setRequired()
            ->addRule(Form::EMAIL);
        $form->addPassword('password', 'Passowrd')
            ->setRequired();
        $form->addSubmit('submit');

        $form->onSuccess[] = function (Form $form, array $values): void {
        };

        return $form;
    }

    protected function createComponentCustomForm(): CustomForm
    {
        $form = new CustomForm();
//        $form->addGroup('General');
        $form->addCustomText('custom_text', 'Custom text')
            ->setRequired();
        $form->addTextArea('custom_textarea', 'Custom textarea');
        $form->addSubmit('submit');

        $form->onSuccess[] = function (Form $form, array $values): void {
        };

        return $form;
    }
}
