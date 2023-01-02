<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Tests\Rule\LatteTemplatesRule\Annotations\Fixtures\ComponentClassParent;

use Nette\Application\UI\Control;
use Placeholder\ComponentParentCall;
use Placeholder\ComponentParentClass;
use Placeholder\ComponentParentMethod;

/**
 * @phpstan-latte-component ComponentParentClass $parentClassComponent
 * @phpstan-latte-component ComponentParentClass $classComponent
 * @phpstan-latte-component ComponentParentClass $myComponent
 */
// COLLECT: NODE NetteApplicationUIControl {"className":"ParentControl"}
abstract class ParentControl extends Control
{
    /**
     * @phpstan-latte-component ComponentParentMethod $methodComponent
     * @phpstan-latte-component ComponentParentMethod $myComponent
     */
    protected function setComponentsParentMethod($param)
    {
        $this['myComponent'] = $param;
    }

    public function renderParentClass()
    {
        // resolved in child class
        // COLLECT: TEMPLATE ParentControl.renderParentClass.latte SomeControl::renderParentClass ["presenter","control"] ["parentClassComponent","classComponent","myComponent"]
        $this->template->render(__DIR__ . '/ParentControl.renderParentClass.latte');
    }

    /**
     * @phpstan-latte-component ComponentParentMethod $methodComponent
     * @phpstan-latte-component ComponentParentMethod $myComponent
     */
    public function renderParentMethod()
    {
        // resolved in child class
        // COLLECT: TEMPLATE ParentControl.renderParentMethod.latte SomeControl::renderParentMethod ["presenter","control"] ["parentClassComponent","classComponent","myComponent","methodComponent"]
        $this->template->render(__DIR__ . '/ParentControl.renderParentMethod.latte');
    }

    public function renderParentCall()
    {
        /**
         * @phpstan-latte-component ComponentParentCall $callComponent
         * @phpstan-latte-component ComponentParentCall $myComponent
         */
        // resolved in child class
        // COLLECT: TEMPLATE ParentControl.renderParentCall.latte SomeControl::renderParentCall ["presenter","control"] ["parentClassComponent","classComponent","myComponent","callComponent"]
        $this->template->render(__DIR__ . '/ParentControl.renderParentCall.latte');
    }

    public function renderParentIndirect($param)
    {
        $this->setComponentsParentMethod($param);
        // resolved in child class
        // COLLECT: TEMPLATE ParentControl.renderParentIndirect.latte SomeControl::renderParentIndirect ["presenter","control"] ["parentClassComponent","classComponent","myComponent","methodComponent"]
        $this->template->render(__DIR__ . '/ParentControl.renderParentIndirect.latte');
    }
}
