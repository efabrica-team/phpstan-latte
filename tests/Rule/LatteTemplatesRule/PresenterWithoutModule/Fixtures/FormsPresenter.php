<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Tests\Rule\LatteTemplatesRule\PresenterWithoutModule\Fixtures;

use Efabrica\PHPStanLatte\Tests\Rule\LatteTemplatesRule\PresenterWithoutModule\Source\CustomForm;
use Efabrica\PHPStanLatte\Tests\Rule\LatteTemplatesRule\PresenterWithoutModule\Source\Slide;
use Nette\Application\UI\Form;
use Nette\Forms\Container;
use Nette\Forms\Controls\SubmitButton;

final class FormsPresenter extends ParentPresenter
{
    public Slide $slide;

    public function actionDefault(): void
    {
        parent::actionDefault();
        $this->template->dynamicVariable = $this->name;
        $this->template->slide = $this->slide;
    }

    protected function createComponentFirstForm(): Form
    {
        $form = new Form();
        $form->setTranslator(null);
        $form->addText('text', 'Text')
            ->setRequired();
        $form->addTextArea('textarea', 'Textarea')
            ->setHtmlAttribute('class', 'textarea-class');
        $submit = new SubmitButton();
        $form->addComponent($submit, 'submit');

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
        $form->addCheckboxList('checkbox_list', 'Checkbox list', [
            'item1' => 'First item',
            'item2' => 'Second item',
            'item3' => 'Third item',
        ]);
        $form->addRadioList('radio_list', 'Radio list', [
            1 => 'First item',
            2 => 'Second item',
            3 => 'Third item',
        ]);
        $form->addText('username', 'Username')
            ->setRequired()
            ->addRule(Form::EMAIL);
        $form->addPassword('password', 'Passowrd')
            ->setRequired();
        $dynamicVariable = $this->name;
        $form->addText($dynamicVariable, 'Dynamic name (variable)');

        $slide = $this->slide;
        $form->addPassword($slide->name, 'Dynamic name (property fetch)');

        $container = $form->addContainer($slide->id);
        $container->addText('title', 'Title');
        $container->addImageButton('image', 'Image');

        $multiContainer = $form->addContainer('multi');
        $slideTitleContainer = $multiContainer->addContainer($slide->title);
        $slideTitleContainer->addText('en');
        $slideTitleContainer->addText('sk');

        $defaults = [];
        $form->setDefaults($defaults);

        $submit = new SubmitButton();
        $submitName = 'submit';
        $form->addComponent($submit, $submitName);

        $form->onSuccess[] = function (Form $form, array $values): void {
        };

        return $form;
    }

    protected function createComponentCustomForm(): CustomForm
    {
        $form = new CustomForm('custom parameter');
        $form->addGroup('General');
        $form->addCustomText('custom_text', 'Custom text')
            ->setRequired();
        $form->addCustomText();
        $form->addTextArea('custom_textarea', 'Custom textarea');
        $submit = new SubmitButton();
        $form->addComponent($submit, 'submit');

        $form->onSuccess[] = function (Form $form, array $values): void {
        };

        return $form;
    }

    protected function createComponentIndirectForm(): Form
    {
        return $this->createIndirectForm();
    }

    protected function createComponentIndirectThroughtParentForm(): Form
    {
        return $this->parentCreateIndirectForm();
    }

    protected function createIndirectForm(): Form
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
        $submit = new SubmitButton();
        $form->addComponent($submit, 'submit');

        $form->onSuccess[] = function (Form $form, array $values): void {
        };

        return $form;
    }

    protected function createComponentIndirectFormFields(): Form
    {
        $form = new Form();
        $form->setTranslator(null);
        $this->createIndirectFormFields($form);

        $form->onSuccess[] = function (Form $form, array $values): void {
        };

        return $form;
    }

    protected function createIndirectFormFields(Container $form): void
    {
        $form->addSelect('select', 'Select', ['item1', 'item2', 'item3']);
        $form->addCheckbox('checkbox', 'Checkbox');
        $form->addText('username', 'Username')
            ->setRequired()
            ->addRule(Form::EMAIL);
        $form->addPassword('password', 'Passowrd')
            ->setRequired();
        $submit = new SubmitButton();
        $form->addComponent($submit, 'submit');
    }

    protected function createComponentContainerForm(): Form
    {
        $form = new Form();
        $form->setMethod('get');
        $form->addCheckbox('checkbox', 'Checkbox');
        $part1 = $form->addContainer('part1');
        $part1->addText('text1', 'Text 1');
        $part1->addSubmit('submit1', 'Submit 1');

        $part2 = $form->addContainer('part2');
        $part2->addText('text2', 'Text 2');
        $part2->addSubmit('submit2', 'Submit 2');

        $part3 = $form->addContainer('part3');
        for ($i = 0; $i < 2; $i++) {
            $subContainer = $part3->addContainer($i);
            $subContainer->addText('title', 'Title');
            $subContainer->addImageButton('image', 'Image');
        }

        $form->onSuccess[] = function (Form $form, array $values): void {
        };

        return $form;
    }

    protected function createComponentFormWithMultiContainer(): Form
    {
        $form = new Form();
        for ($i = 0; $i < 5; $i++) {
            $container = $form->addContainer($i);
            $container->addText('text');
        }
        $form->addSubmit('submit', 'Submit');
        return $form;
    }

    protected function createComponentFormWithDynamicMultiContainer(): Form
    {
        $form = new Form();
        for ($i = 0; $i < strlen($this->name); $i++) {
            $container = $form->addContainer($i);
            $container->addText('text');
        }
        $form->addSubmit('submit', 'Submit');
        return $form;
    }
}
