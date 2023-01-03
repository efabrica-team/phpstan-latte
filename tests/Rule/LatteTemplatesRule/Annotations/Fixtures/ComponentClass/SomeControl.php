<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Tests\Rule\LatteTemplatesRule\Annotations\Fixtures\ComponentClass;

use Nette\Application\UI\Control;
use Placeholder\ComponentCall;
use Placeholder\ComponentClass;
use Placeholder\ComponentIndirect;
use Placeholder\ComponentMethod;

/**
 * @phpstan-latte-component ComponentClass $classComponent
 * @phpstan-latte-component ComponentClass $myComponent
 */
// COLLECT: NODE NetteApplicationUIControl {"className":"SomeControl"}
final class SomeControl extends Control
{
    public function renderClass()
    {
        // COLLECT: TEMPLATE SomeControl.renderClass.latte SomeControl::renderClass ["presenter","control"] ["classComponent","myComponent"]
        $this->template->render(__DIR__ . '/SomeControl.renderClass.latte');
    }

    /**
     * @phpstan-latte-component ComponentMethod $methodComponent
     * @phpstan-latte-component ComponentMethod $myComponent
     */
    public function renderMethod()
    {
        // COLLECT: TEMPLATE SomeControl.renderMethod.latte SomeControl::renderMethod ["presenter","control"] ["classComponent","myComponent","methodComponent"]
        $this->template->render(__DIR__ . '/SomeControl.renderMethod.latte');
    }

    public function renderCall()
    {
        /**
         * @phpstan-latte-component ComponentCall $callComponent
         * @phpstan-latte-component ComponentCall $myComponent
         */
        // COLLECT: TEMPLATE SomeControl.renderCall.latte SomeControl::renderCall ["presenter","control"] ["classComponent","myComponent","callComponent"]
        $this->template->render(__DIR__ . '/SomeControl.renderCall.latte');
    }

    /**
     * @phpstan-latte-component ComponentMethod $methodComponent
     * @phpstan-latte-component ComponentMethod $myComponent
     */
    public function renderAll()
    {
        /**
         * @phpstan-latte-component ComponentCall $callComponent
         * @phpstan-latte-component ComponentCall $myComponent
         */
        // COLLECT: TEMPLATE SomeControl.renderAll.latte SomeControl::renderAll ["presenter","control"] ["classComponent","myComponent","methodComponent","callComponent"]
        $this->template->render(__DIR__ . '/SomeControl.renderAll.latte');
    }

    private function setComponents($param)
    {
        /** @phpstan-latte-component ComponentIndirect */
        $this['myComponent'] = $param;
    }

    public function renderIndirect($param)
    {
        $this->setComponents($param);
        // COLLECT: TEMPLATE SomeControl.renderIndirect.latte SomeControl::renderIndirect ["presenter","control"] ["classComponent","myComponent"]
        $this->template->render(__DIR__ . '/SomeControl.renderIndirect.latte');
    }

    /**
     * @phpstan-latte-component ComponentIndirect $myComponent
     * @phpstan-latte-component ComponentIndirect $indirectComponent
     */
    private function setComponentsMethod($param)
    {
        $this['myComponent'] = $param;
    }

    public function renderIndirectMethod($param)
    {
        $this->setComponentsMethod($param);
        // COLLECT: TEMPLATE SomeControl.renderIndirectMethod.latte SomeControl::renderIndirectMethod ["presenter","control"] ["classComponent","myComponent","indirectComponent"]
        $this->template->render(__DIR__ . '/SomeControl.renderIndirectMethod.latte');
    }

    /**
     * @phpstan-latte-component ComponentMethod $myComponent
     * @phpstan-latte-component ComponentMethod $indirectComponent
     */
    public function renderIndirectMethodOwn($param)
    {
        $this->setComponentsMethod($param);
        // COLLECT: TEMPLATE SomeControl.renderIndirectMethodOwn.latte SomeControl::renderIndirectMethodOwn ["presenter","control"] ["classComponent","myComponent","indirectComponent"]
        $this->template->render(__DIR__ . '/SomeControl.renderIndirectMethodOwn.latte');
    }
}
