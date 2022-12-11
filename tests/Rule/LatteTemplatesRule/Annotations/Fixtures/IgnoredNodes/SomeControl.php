<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Tests\Rule\LatteTemplatesRule\Annotations\Fixtures\IgnoredNodes;

use Nette\Application\UI\Control;

// COLLECT: NODE NetteApplicationUIControl {"className":"SomeControl"}
final class SomeControl extends Control
{
    public function render()
    {
        // COLLECT: TEMPLATE SomeControl.latte SomeControl::render ["presenter","control"] []
        $this->template->render(__DIR__ . '/SomeControl.latte');
    }

    // ERROR: Cannot resolve latte template for SomeControl::renderIgnored().
    public function renderIgnored()
    {
        /** @phpstan-latte-ignore */
        $this->template->render(__DIR__ . '/error.latte');
    }

    public function renderIgnoredVar()
    {
        /** @phpstan-latte-ignore */
        $this->template->ignoredVar = 'ignoredVar';
        // COLLECT: TEMPLATE SomeControl.latte SomeControl::renderIgnoredVar ["presenter","control"] []
        $this->template->render(__DIR__ . '/SomeControl.latte');
    }

    public function renderIgnoredComponent()
    {
        /** @phpstan-latte-ignore */
        $this->addComponent(new SomeControl(), 'ignoredComponent');
        // COLLECT: TEMPLATE SomeControl.latte SomeControl::renderIgnoredComponent ["presenter","control"] []
        $this->template->render(__DIR__ . '/SomeControl.latte');
    }

    // ERROR: Cannot resolve latte template for SomeControl::renderIgnoredTemplatePath().
    public function renderIgnoredTemplatePath()
    {
        /** @phpstan-latte-ignore */
        $this->template->setFile(__DIR__ . '/error.latte');
        $this->template->render();
    }

    public function renderIgnoredMethodCall()
    {
        /** @phpstan-latte-ignore */
        $this->setVariablesCallIgnored();
        // COLLECT: TEMPLATE SomeControl.latte SomeControl::renderIgnoredMethodCall ["presenter","control"] []
        $this->template->render(__DIR__ . '/SomeControl.latte');
    }

    public function setVariablesCallIgnored()
    {
        $this->template->ignoredVar = 'ignoredVar';
    }
}
