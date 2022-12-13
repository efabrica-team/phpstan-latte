<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Tests\Rule\LatteTemplatesRule\Annotations\Fixtures\VariableClass;

use Nette\Application\UI\Control;
use Placeholder\VarCall;
use Placeholder\VarClass;
use Placeholder\VarIndirect;
use Placeholder\VarMethod;

/**
 * @phpstan-latte-var VarClass $classVar
 * @phpstan-latte-var VarClass $myVar
 */
// COLLECT: NODE NetteApplicationUIControl {"className":"SomeControl"}
final class SomeControl extends Control
{
    public function renderClass()
    {
        // COLLECT: TEMPLATE SomeControl.renderClass.latte SomeControl::renderClass ["presenter","control","classVar","myVar","explicitParam"] []
        $this->template->render(__DIR__ . '/SomeControl.renderClass.latte', ['explicitParam' => 'a']);
    }

    /**
     * @phpstan-latte-var VarMethod $methodVar
     * @phpstan-latte-var VarMethod $myVar
     */
    public function renderMethod()
    {
        // COLLECT: TEMPLATE SomeControl.renderMethod.latte SomeControl::renderMethod ["presenter","control","classVar","myVar","methodVar","explicitParam"] []
        $this->template->render(__DIR__ . '/SomeControl.renderMethod.latte', ['explicitParam' => 'a']);
    }

    public function renderCall()
    {
        /**
         * @phpstan-latte-var VarCall $callVar
         * @phpstan-latte-var VarCall $myVar
         * @phpstan-latte-var VarCall $explicitParam
         */
        // COLLECT: TEMPLATE SomeControl.renderCall.latte SomeControl::renderCall ["presenter","control","classVar","myVar","explicitParam","callVar"] []
        $this->template->render(__DIR__ . '/SomeControl.renderCall.latte', ['explicitParam' => 'a']);
    }

    /**
     * @phpstan-latte-var VarMethod $methodVar
     * @phpstan-latte-var VarMethod $myVar
     */
    public function renderAll()
    {
        /**
         * @phpstan-latte-var VarCall $callVar
         * @phpstan-latte-var VarCall $myVar
         * @phpstan-latte-var VarCall $explicitParam
         */
        // COLLECT: TEMPLATE SomeControl.renderAll.latte SomeControl::renderAll ["presenter","control","classVar","myVar","methodVar","explicitParam","callVar"] []
        $this->template->render(__DIR__ . '/SomeControl.renderAll.latte', ['explicitParam' => 'a']);
    }

    private function setVariables($param)
    {
        /** @phpstan-latte-var VarIndirect */
        $this->template->myVar = $param;
    }

    public function renderIndirect($param)
    {
        $this->setVariables($param);
        // COLLECT: TEMPLATE SomeControl.renderIndirect.latte SomeControl::renderIndirect ["presenter","control","classVar","myVar","explicitParam"] []
        $this->template->render(__DIR__ . '/SomeControl.renderIndirect.latte', ['explicitParam' => 'a']);
    }

    /**
     * @phpstan-latte-var VarIndirect $myVar
     * @phpstan-latte-var VarIndirect $indirectVar
     */
    private function setVariablesMethod($param)
    {
        $this->template->myVar = $param;
    }

    public function renderIndirectMethod($param)
    {
        $this->setVariablesMethod($param);
        // COLLECT: TEMPLATE SomeControl.renderIndirectMethod.latte SomeControl::renderIndirectMethod ["presenter","control","classVar","myVar","indirectVar","explicitParam"] []
        $this->template->render(__DIR__ . '/SomeControl.renderIndirectMethod.latte', ['explicitParam' => 'a']);
    }

    /**
     * @phpstan-latte-var VarMethod $myVar
     * @phpstan-latte-var VarMethod $indirectVar
     */
    public function renderIndirectMethodOwn($param)
    {
        $this->setVariablesMethod($param);
        // COLLECT: TEMPLATE SomeControl.renderIndirectMethodOwn.latte SomeControl::renderIndirectMethodOwn ["presenter","control","classVar","myVar","indirectVar","explicitParam"] []
        $this->template->render(__DIR__ . '/SomeControl.renderIndirectMethodOwn.latte', ['explicitParam' => 'a']);
    }
}
