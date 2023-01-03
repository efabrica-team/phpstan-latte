<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Tests\Rule\LatteTemplatesRule\Annotations\Fixtures\ComponentClassParent;

use Placeholder\ComponentCall;
use Placeholder\ComponentClass;
use Placeholder\ComponentIndirect;
use Placeholder\ComponentMethod;

/**
 * @phpstan-latte-component ComponentClass $classComponent
 * @phpstan-latte-component ComponentClass $myComponent
 */
// COLLECT: NODE NetteApplicationUIControl {"className":"SomeControl"}
final class SomeControl extends ParentControl
{
    public function renderClass()
    {
        // COLLECT: TEMPLATE SomeControl.renderClass.latte SomeControl::renderClass ["presenter","control"] ["parentClassComponent","classComponent","myComponent"]
        $this->template->render(__DIR__ . '/SomeControl.renderClass.latte');
    }

    /**
     * @phpstan-latte-component ComponentMethod $methodComponent
     * @phpstan-latte-component ComponentMethod $myComponent
     */
    public function renderMethod()
    {
        // COLLECT: TEMPLATE SomeControl.renderMethod.latte SomeControl::renderMethod ["presenter","control"] ["parentClassComponent","classComponent","myComponent","methodComponent"]
        $this->template->render(__DIR__ . '/SomeControl.renderMethod.latte');
    }

    public function renderCall()
    {
        /**
         * @phpstan-latte-component ComponentCall $callComponent
         * @phpstan-latte-component ComponentCall $myComponent
         */
        // COLLECT: TEMPLATE SomeControl.renderCall.latte SomeControl::renderCall ["presenter","control"] ["parentClassComponent","classComponent","myComponent","callComponent"]
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
        // COLLECT: TEMPLATE SomeControl.renderAll.latte SomeControl::renderAll ["presenter","control"] ["parentClassComponent","classComponent","myComponent","methodComponent","callComponent"]
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
        // COLLECT: TEMPLATE SomeControl.renderIndirect.latte SomeControl::renderIndirect ["presenter","control"] ["parentClassComponent","classComponent","myComponent"]
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
        // COLLECT: TEMPLATE SomeControl.renderIndirectMethod.latte SomeControl::renderIndirectMethod ["presenter","control"] ["parentClassComponent","classComponent","myComponent","indirectComponent"]
        $this->template->render(__DIR__ . '/SomeControl.renderIndirectMethod.latte');
    }

    public function renderParentIndirectMethod($param)
    {
        $this->setComponentsParentMethod($param);
        // COLLECT: TEMPLATE SomeControl.renderParentIndirectMethod.latte SomeControl::renderParentIndirectMethod ["presenter","control"] ["parentClassComponent","classComponent","myComponent","methodComponent"]
        $this->template->render(__DIR__ . '/SomeControl.renderParentIndirectMethod.latte');
    }

    public function renderParentIndirectRender($param)
    {
        // COLLECT: TEMPLATE ParentControl.renderParentIndirect.latte SomeControl::renderParentIndirectRender ["presenter","control"] ["parentClassComponent","classComponent","myComponent","methodComponent"]
        $this->renderParentIndirect($param);
    }
}
