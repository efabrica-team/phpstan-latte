<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Tests\Rule\LatteTemplatesRule\Annotations\Fixtures\Template;

use Nette\Application\UI\Control;

// COLLECT: NODE NetteApplicationUIControl {"className":"SomeControl"}
final class SomeControl extends Control
{
    public function render()
    {
        $this->template->method = __FUNCTION__;
        // COLLECT: TEMPLATE SomeControl.latte SomeControl::render ["presenter","control","method","explicitParam"] []
        /** @phpstan-latte-template {dir}/SomeControl.latte */
        $this->template->render(__DIR__ . '/error.latte', ['explicitParam' => 'a']);
    }

    public function renderMultiple()
    {
        $this->template->method = __FUNCTION__;
        // COLLECT: TEMPLATE SomeControl.latte SomeControl::renderMultiple ["presenter","control","method","explicitParam"] []
        // COLLECT: TEMPLATE SomeControl.php.latte SomeControl::renderMultiple ["presenter","control","method","explicitParam"] []
        /**
         * @phpstan-latte-template {dir}/SomeControl.latte
         * @phpstan-latte-template {dir}/SomeControl.php.latte
         */
        $this->template->render(__DIR__ . '/error.latte', ['explicitParam' => 'a']);
    }

    // ERROR: Cannot resolve latte template for SomeControl::renderIgnored().
    public function renderIgnored()
    {
        $this->template->method = __FUNCTION__;
        /**
         * @phpstan-latte-ignore // ignore has priority
         * @phpstan-latte-template {dir}/SomeControl.latte
         */
        $this->template->render(__DIR__ . '/error.latte', ['explicitParam' => 'a']);
    }

    /**
     * @phpstan-latte-template {dir}/SomeControl.latte
     */
    // COLLECT: TEMPLATE SomeControl.latte SomeControl::renderMethodIgnored ["presenter","control","method"] []
    public function renderMethodIgnored()
    {
        $this->template->method = __FUNCTION__;
        /** @phpstan-latte-ignore */
        $this->template->render(__DIR__ . '/error.latte', ['explicitParam' => 'a']);
    }

    /**
     * @phpstan-latte-template {dir}/SomeControl.latte
     */
    // COLLECT: TEMPLATE SomeControl.latte SomeControl::renderMethodNoRender ["presenter","control","method"] []
    public function renderMethodNoRender()
    {
        $this->template->method = __FUNCTION__;
    }

    /**
     * @phpstan-latte-template {dir}/SomeControl.latte
     */
    public function notRenderingMethod()
    {
        $this->template->method = __FUNCTION__;
    }

    public function renderPlaceholders()
    {
        $this->template->method = __FUNCTION__;

        // COLLECT: TEMPLATE SomeControl.latte SomeControl::renderPlaceholders ["presenter","control","method"] []
        /** @phpstan-latte-template {dir}/SomeControl.latte */
        $this->template->render(__DIR__ . '/error.latte');

        // COLLECT: TEMPLATE SomeControl.php.latte SomeControl::renderPlaceholders ["presenter","control","method"] []
        /** @phpstan-latte-template {file}.latte */
        $this->template->render(__DIR__ . '/error.latte');

        // COLLECT: TEMPLATE SomeControl.php.baseName.latte SomeControl::renderPlaceholders ["presenter","control","method"] []
        /** @phpstan-latte-template {dir}/{baseName}.baseName.latte */
        $this->template->render(__DIR__ . '/error.latte');

        // COLLECT: TEMPLATE SomeControl.fileName.latte SomeControl::renderPlaceholders ["presenter","control","method"] []
        /** @phpstan-latte-template {dir}/{fileName}.fileName.latte */
        $this->template->render(__DIR__ . '/error.latte');

        // COLLECT: TEMPLATE SomeControl.className.latte SomeControl::renderPlaceholders ["presenter","control","method"] []
        /** @phpstan-latte-template {dir}/{className}.className.latte */
        $this->template->render(__DIR__ . '/error.latte');
    }
}
