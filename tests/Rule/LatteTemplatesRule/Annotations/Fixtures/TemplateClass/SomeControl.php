<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Tests\Rule\LatteTemplatesRule\Annotations\Fixtures\TemplateClass;

use Nette\Application\UI\Control;

/**
 * @phpstan-latte-template {dir}/SomeControl.latte
 */
// COLLECT: NODE NetteApplicationUIControl {"className":"SomeControl"}
final class SomeControl extends Control
{

    // COLLECT: TEMPLATE SomeControl.latte SomeControl::render ["presenter","control","method"] []
    public function render()
    {
        $this->template->method = __FUNCTION__;
        // COLLECT: TEMPLATE SomeControl.render.latte SomeControl::render ["presenter","control","method","explicitParam"] []
        $this->template->render(__DIR__ . '/SomeControl.render.latte', ['explicitParam' => 'a']);
    }

    // COLLECT: TEMPLATE SomeControl.latte SomeControl::renderAnother ["presenter","control","method"] []
    public function renderAnother()
    {
        $this->template->method = __FUNCTION__;
        // COLLECT: TEMPLATE SomeControl.another.latte SomeControl::renderAnother ["presenter","control","method","explicitParam"] []
        $this->template->render(__DIR__ . '/SomeControl.another.latte', ['explicitParam' => 'a']);
    }

    // COLLECT: TEMPLATE SomeControl.latte SomeControl::renderMethod ["presenter","control","method"] []
    // COLLECT: TEMPLATE SomeControl.method.latte SomeControl::renderMethod ["presenter","control","method"] []

    /** @phpstan-latte-template {dir}/SomeControl.method.latte */
    public function renderMethod()
    {
        $this->template->method = __FUNCTION__;
        // COLLECT: TEMPLATE SomeControl.method.render.latte SomeControl::renderMethod ["presenter","control","method","explicitParam"] []
        $this->template->render(__DIR__ . '/SomeControl.method.render.latte', ['explicitParam' => 'a']);
    }

    // COLLECT: TEMPLATE SomeControl.latte SomeControl::renderNoRender ["presenter","control","method"] []
    public function renderNoRender()
    {
        $this->template->method = __FUNCTION__;
    }

    // COLLECT: TEMPLATE SomeControl.latte SomeControl::renderCall ["presenter","control","method"] []
    public function renderCall()
    {
        $this->template->method = __FUNCTION__;
        // COLLECT: TEMPLATE SomeControl.call.latte SomeControl::renderCall ["presenter","control","method","explicitParam"] []
        /** @phpstan-latte-template {dir}/SomeControl.call.latte */
        $this->template->render(__DIR__ . '/error.latte', ['explicitParam' => 'a']);
    }

    // COLLECT: TEMPLATE SomeControl.latte SomeControl::renderMethodCall ["presenter","control","method"] []
    // COLLECT: TEMPLATE SomeControl.methodCall.method.latte SomeControl::renderMethodCall ["presenter","control","method"] []

    /** @phpstan-latte-template {dir}/SomeControl.methodCall.method.latte */
    public function renderMethodCall()
    {
        $this->template->method = __FUNCTION__;
        // COLLECT: TEMPLATE SomeControl.methodCall.call.latte SomeControl::renderMethodCall ["presenter","control","method","explicitParam"] []
        /** @phpstan-latte-template {dir}/SomeControl.methodCall.call.latte */
        $this->template->render(__DIR__ . '/error.latte', ['explicitParam' => 'a']);
    }
}
