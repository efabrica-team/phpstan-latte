<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Tests\Rule\LatteTemplatesRule\Annotations\Fixtures\Component;

use Nette\Application\UI\Control;
use Placeholder\ComponentA;

// COLLECT: NODE NetteApplicationUIControl {"className":"SomeControl"}
final class SomeControl extends Control
{
    public function render()
    {
        // COLLECT: TEMPLATE SomeControl.render.latte SomeControl::render ["presenter","control","flashes"] ["myComponent"]
        /** @phpstan-latte-component ComponentA $myComponent */
        $this->template->render(__DIR__ . '/SomeControl.render.latte');
    }

    /** @phpstan-latte-component ComponentA $methodComponent */
    public function renderMethod()
    {
        // COLLECT: TEMPLATE SomeControl.renderMethod.latte SomeControl::renderMethod ["presenter","control","flashes"] ["methodComponent","myComponent"]
        /** @phpstan-latte-component ComponentA $myComponent */
        $this->template->render(__DIR__ . '/SomeControl.renderMethod.latte');
    }

    /** @phpstan-latte-component ComponentA */
    public function renderMethodNoName()
    {
        // COLLECT: TEMPLATE SomeControl.renderMethodNoName.latte SomeControl::renderMethodNoName ["presenter","control","flashes"] ["myComponent"]
        /** @phpstan-latte-component ComponentA $myComponent */
        $this->template->render(__DIR__ . '/SomeControl.renderMethodNoName.latte');
    }

    public function renderAddComponent($param)
    {
        /** @phpstan-latte-component ComponentA */
        $this->addComponent($param, 'myComponent');
        // COLLECT: TEMPLATE SomeControl.renderAddComponent.latte SomeControl::renderAddComponent ["presenter","control","flashes"] ["myComponent"]
        $this->template->render(__DIR__ . '/SomeControl.renderAddComponent.latte');
    }

    public function renderAssign($param)
    {
        /** @phpstan-latte-component ComponentA */
        $this['myComponent'] = $param;
        // COLLECT: TEMPLATE SomeControl.renderAssign.latte SomeControl::renderAssign ["presenter","control","flashes"] ["myComponent"]
        $this->template->render(__DIR__ . '/SomeControl.renderAssign.latte');
    }

    public function renderAssignName($param)
    {
        /** @phpstan-latte-component ComponentA $myComponent */
        $this['myComponent'] = $param;
        // COLLECT: TEMPLATE SomeControl.renderAssignName.latte SomeControl::renderAssignName ["presenter","control","flashes"] ["myComponent"]
        $this->template->render(__DIR__ . '/SomeControl.renderAssignName.latte');
    }

    public function renderAssignDifferent($param)
    {
        /** @phpstan-latte-component ComponentA $secondComponent */
        $this['myComponent'] = $param;
        // COLLECT: TEMPLATE SomeControl.renderAssignDifferent.latte SomeControl::renderAssignDifferent ["presenter","control","flashes"] ["secondComponent"]
        $this->template->render(__DIR__ . '/SomeControl.renderAssignDifferent.latte');
    }

    public function renderAssignNoName($param)
    {
        /** @phpstan-latte-component ComponentA $secondComponent */
        $this[$param] = $param;
        // COLLECT: TEMPLATE SomeControl.renderAssignNoName.latte SomeControl::renderAssignNoName ["presenter","control","flashes"] ["secondComponent"]
        $this->template->render(__DIR__ . '/SomeControl.renderAssignNoName.latte');
    }

    public function renderAssignMultiple($param)
    {
        /**
         * @phpstan-latte-component ComponentA $myComponent
         * @phpstan-latte-component ComponentA $secondComponent
         */
        $this['myComponent'] = $param;
        // COLLECT: TEMPLATE SomeControl.renderAssignMultiple.latte SomeControl::renderAssignMultiple ["presenter","control","flashes"] ["myComponent","secondComponent"]
        $this->template->render(__DIR__ . '/SomeControl.renderAssignMultiple.latte');
    }

    private function setComponents($param)
    {
        /** @phpstan-latte-component ComponentA */
        $this['myComponent'] = $param;
    }

    public function renderIndirect($param)
    {
        $this->setComponents($param);
        // COLLECT: TEMPLATE SomeControl.renderIndirect.latte SomeControl::renderIndirect ["presenter","control","flashes"] ["myComponent"]
        $this->template->render(__DIR__ . '/SomeControl.renderIndirect.latte');
    }

    /** @phpstan-latte-component ComponentA $myComponent */
    private function setComponentsMethod($param)
    {
        $this['myComponent'] = $param;
    }

    public function renderIndirectMethod($param)
    {
        $this->setComponentsMethod($param);
        // COLLECT: TEMPLATE SomeControl.renderIndirectMethod.latte SomeControl::renderIndirectMethod ["presenter","control","flashes"] ["myComponent"]
        $this->template->render(__DIR__ . '/SomeControl.renderIndirectMethod.latte');
    }
}
