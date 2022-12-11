<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Tests\Rule\LatteTemplatesRule\Annotations\Fixtures\IgnoredMethod;

use Nette\Application\UI\Control;

// COLLECT: NODE NetteApplicationUIControl {"className":"SomeControl"}
final class SomeControl extends Control
{
    public function render()
    {
        $this->setVariables();
        $this->setVariablesIgnored();
        // COLLECT: TEMPLATE SomeControl.latte SomeControl::render ["presenter","control","someVar"] []
        $this->template->render(__DIR__ . '/SomeControl.latte');
    }

    /**
     * @phpstan-latte-ignore
     */
    public function renderIgnored()
    {
        $this->setVariables();
        $this->setVariablesIgnored();
        $this->template->render(__DIR__ . '/error.latte');
    }

    // ERROR: Cannot resolve latte template for SomeControl::renderIgnoredIndirect().
    public function renderIgnoredIndirect()
    {
        $this->renderIgnored();
    }

    public function renderTemplatePath()
    {
        $this->setTemplatePath();
        $this->setTemplatePathIgnored();
        // COLLECT: TEMPLATE SomeControl.other.latte SomeControl::renderTemplatePath ["presenter","control"] []
        $this->template->render();
    }

    public function setVariables()
    {
        $this->template->someVar = 'someVar';
    }

    /**
     * @phpstan-latte-ignore
     */
    public function setVariablesIgnored()
    {
        $this->setVariablesIgnoredSecondary();
        $this->template->ignoredVar = 'ignoredVar';
    }

    public function setVariablesIgnoredSecondary()
    {
        $this->setVariablesIgnoredSecondary();
        $this->template->secondaryIgnoredVar = 'secondaryIgnoredVar';
    }

    public function setTemplatePath()
    {
        $this->template->setFile(__DIR__ . '/SomeControl.other.latte');
    }

    /**
     * @phpstan-latte-ignore
     */
    public function setTemplatePathIgnored()
    {
        $this->template->setFile(__DIR__ . '/error.latte');
    }

    /**
     * @phpstan-latte-ignore
     */
    public function createComponentIgnoredComponent(): SomeControl
    {
        return new SomeControl();
    }
}
