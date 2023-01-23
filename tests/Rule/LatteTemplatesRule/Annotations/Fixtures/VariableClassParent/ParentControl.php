<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Tests\Rule\LatteTemplatesRule\Annotations\Fixtures\VariableClassParent;

use Nette\Application\UI\Control;
use Placeholder\VarParentCall;
use Placeholder\VarParentClass;
use Placeholder\VarParentMethod;

/**
 * @phpstan-latte-var VarParentClass $parentClassVar
 * @phpstan-latte-var VarParentClass $classVar
 * @phpstan-latte-var VarParentClass $myVar
 */
// COLLECT: NODE NetteApplicationUIControl {"className":"ParentControl"}
abstract class ParentControl extends Control
{
    /**
     * @phpstan-latte-var VarParentMethod $methodVar
     * @phpstan-latte-var VarParentMethod $myVar
     */
    protected function setVariablesParentMethod($param)
    {
        $this->template->myVar = $param;
    }

    public function renderParentClass()
    {
        // resolved in child class
        // COLLECT: TEMPLATE ParentControl.renderParentClass.latte SomeControl::renderParentClass ["presenter","control","flashes","parentClassVar","classVar","myVar","explicitParam"] []
        $this->template->render(__DIR__ . '/ParentControl.renderParentClass.latte', ['explicitParam' => 'a']);
    }

    /**
     * @phpstan-latte-var VarParentMethod $methodVar
     * @phpstan-latte-var VarParentMethod $myVar
     */
    public function renderParentMethod()
    {
        // resolved in child class
        // COLLECT: TEMPLATE ParentControl.renderParentMethod.latte SomeControl::renderParentMethod ["presenter","control","flashes","parentClassVar","classVar","myVar","methodVar","explicitParam"] []
        $this->template->render(__DIR__ . '/ParentControl.renderParentMethod.latte', ['explicitParam' => 'a']);
    }

    public function renderParentCall()
    {
        /**
         * @phpstan-latte-var VarParentCall $callVar
         * @phpstan-latte-var VarParentCall $myVar
         * @phpstan-latte-var VarParentCall $explicitParam
         */
        // resolved in child class
        // COLLECT: TEMPLATE ParentControl.renderParentCall.latte SomeControl::renderParentCall ["presenter","control","flashes","parentClassVar","classVar","myVar","explicitParam","callVar"] []
        $this->template->render(__DIR__ . '/ParentControl.renderParentCall.latte', ['explicitParam' => 'a']);
    }

    public function renderParentIndirect($param)
    {
        $this->setVariablesParentMethod($param);
        // resolved in child class
        // COLLECT: TEMPLATE ParentControl.renderParentIndirect.latte SomeControl::renderParentIndirect ["presenter","control","flashes","parentClassVar","classVar","myVar","methodVar","explicitParam"] []
        $this->template->render(__DIR__ . '/ParentControl.renderParentIndirect.latte', ['explicitParam' => 'a']);
    }
}
