<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Tests\Rule\LatteTemplatesRule\Annotations\Fixtures\Variable;

use Nette\Application\UI\Control;
use Placeholder\VarA;

// COLLECT: NODE NetteApplicationUIControl {"className":"SomeControl"}
final class SomeControl extends Control
{
    public function render()
    {
        // COLLECT: TEMPLATE SomeControl.render.latte SomeControl::render ["presenter","control","explicitParam","myVar"] []
        /** @phpstan-latte-var VarA $myVar */
        $this->template->render(__DIR__ . '/SomeControl.render.latte', ['explicitParam' => 'a']);
    }

    /** @phpstan-latte-var VarA $methodVar */
    public function renderMethod()
    {
        // COLLECT: TEMPLATE SomeControl.renderMethod.latte SomeControl::renderMethod ["presenter","control","methodVar","explicitParam","myVar"] []
        /** @phpstan-latte-var VarA $myVar */
        $this->template->render(__DIR__ . '/SomeControl.renderMethod.latte', ['explicitParam' => 'a']);
    }

    /** @phpstan-latte-var VarA */
    public function renderMethodNoName()
    {
        // COLLECT: TEMPLATE SomeControl.renderMethodNoName.latte SomeControl::renderMethodNoName ["presenter","control","explicitParam","myVar"] []
        /** @phpstan-latte-var VarA $myVar */
        $this->template->render(__DIR__ . '/SomeControl.renderMethodNoName.latte', ['explicitParam' => 'a']);
    }

    public function renderAssign($param)
    {
        /** @phpstan-latte-var VarA */
        $this->template->myVar = $param;
        // COLLECT: TEMPLATE SomeControl.renderAssign.latte SomeControl::renderAssign ["presenter","control","myVar","explicitParam"] []
        $this->template->render(__DIR__ . '/SomeControl.renderAssign.latte', ['explicitParam' => 'a']);
    }

    public function renderAssignName($param)
    {
        /** @phpstan-latte-var VarA $myVar */
        $this->template->myVar = $param;
        // COLLECT: TEMPLATE SomeControl.renderAssignName.latte SomeControl::renderAssignName ["presenter","control","myVar","explicitParam"] []
        $this->template->render(__DIR__ . '/SomeControl.renderAssignName.latte', ['explicitParam' => 'a']);
    }

    public function renderAssignDifferent($param)
    {
        /** @phpstan-latte-var VarA $secondVar */
        $this->template->myVar = $param;
        // COLLECT: TEMPLATE SomeControl.renderAssignDifferent.latte SomeControl::renderAssignDifferent ["presenter","control","secondVar","explicitParam"] []
        $this->template->render(__DIR__ . '/SomeControl.renderAssignDifferent.latte', ['explicitParam' => 'a']);
    }

    public function renderAssignNoName($param)
    {
        /** @phpstan-latte-var VarA $secondVar */
        $this->template->{$param} = $param;
        // COLLECT: TEMPLATE SomeControl.renderAssignNoName.latte SomeControl::renderAssignNoName ["presenter","control","secondVar","explicitParam"] []
        $this->template->render(__DIR__ . '/SomeControl.renderAssignNoName.latte', ['explicitParam' => 'a']);
    }

    public function renderAssignMultiple($param)
    {
        /**
         * @phpstan-latte-var VarA $myVar
         * @phpstan-latte-var VarA $secondVar
         */
        $this->template->myVar = $param;
        // COLLECT: TEMPLATE SomeControl.renderAssignMultiple.latte SomeControl::renderAssignMultiple ["presenter","control","myVar","secondVar","explicitParam"] []
        $this->template->render(__DIR__ . '/SomeControl.renderAssignMultiple.latte', ['explicitParam' => 'a']);
    }

    public function renderShadowExplicitParam()
    {
        // COLLECT: TEMPLATE SomeControl.renderShadowExplicitParam.latte SomeControl::renderShadowExplicitParam ["presenter","control","explicitParam"] []
        /** @phpstan-latte-var VarA $explicitParam */
        $this->template->render(__DIR__ . '/SomeControl.renderShadowExplicitParam.latte', ['explicitParam' => 'a']);
    }

    /** @phpstan-latte-var VarA $explicitParam */
    public function renderShadowExplicitParamMethod()
    {
        // COLLECT: TEMPLATE SomeControl.renderShadowExplicitParamMethod.latte SomeControl::renderShadowExplicitParamMethod ["presenter","control","explicitParam"] []
        $this->template->render(__DIR__ . '/SomeControl.renderShadowExplicitParamMethod.latte', ['explicitParam' => 'a']);
    }

    /** @phpstan-latte-var VarA $myVar */
    public function renderShadowExplicitParamAssign($param)
    {
        /** @phpstan-latte-var string */
        $this->template->myVar = $param;
        // COLLECT: TEMPLATE SomeControl.renderShadowExplicitParamAssign.latte SomeControl::renderShadowExplicitParamAssign ["presenter","control","myVar","explicitParam"] []
        $this->template->render(__DIR__ . '/SomeControl.renderShadowExplicitParamAssign.latte', ['explicitParam' => 'a']);
    }

    private function setVariables($param)
    {
        /** @phpstan-latte-var VarA */
        $this->template->myVar = $param;
    }

    public function renderIndirect($param)
    {
        $this->setVariables($param);
        // COLLECT: TEMPLATE SomeControl.renderIndirect.latte SomeControl::renderIndirect ["presenter","control","myVar","explicitParam"] []
        $this->template->render(__DIR__ . '/SomeControl.renderIndirect.latte', ['explicitParam' => 'a']);
    }

    /** @phpstan-latte-var VarA $myVar*/
    private function setVariablesMethod($param)
    {
        $this->template->myVar = $param;
    }

    public function renderIndirectMethod($param)
    {
        $this->setVariablesMethod($param);
        // COLLECT: TEMPLATE SomeControl.renderIndirectMethod.latte SomeControl::renderIndirectMethod ["presenter","control","myVar","explicitParam"] []
        $this->template->render(__DIR__ . '/SomeControl.renderIndirectMethod.latte', ['explicitParam' => 'a']);
    }
}
