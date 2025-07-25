<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Tests\Rule\LatteTemplatesRule\PresenterWithoutModule;

use Efabrica\PHPStanLatte\Tests\Rule\LatteTemplatesRule\CollectorResultTestCase;

final class CollectorResultForPresenterTest extends CollectorResultTestCase
{
    protected static function additionalConfigFiles(): array
    {
        return [
            __DIR__ . '/../../../../rules.neon',
            __DIR__ . '/../../../config.neon',
            __DIR__ . '/config.neon',
            __DIR__ . '/mapping.neon',
        ];
    }

    public function testVariables(): void
    {
        $this->analyse([__DIR__ . '/Fixtures/VariablesPresenter.php', __DIR__ . '/Fixtures/ParentPresenter.php'], [
            'NODE NetteApplicationUIPresenter {"className":"VariablesPresenter"}',
            'NODE NetteApplicationUIPresenterStandalone {"className":"VariablesPresenter"}',
            'TEMPLATE arrayShapeParams.latte VariablesPresenter::arrayShapeParams ["startup","startupParent","presenter","control","flashes","a","b","c"] ["parentForm","header","footer"]',
            'TEMPLATE objectShapeParams.latte VariablesPresenter::objectShapeParams ["startup","startupParent","presenter","control","flashes","objectShape"] ["parentForm","header","footer"]',
            'TEMPLATE default.latte VariablesPresenter::default ["startup","startupParent","presenter","control","flashes","title","viaGetTemplate","stringLists","localStrings","dynamic","obj","array1","array2","list1","list2","array1WithoutType","array2WithoutType","list1WithType","list2WithType","variableFromMethodCallOnVariable","someOtherVariableWithDefault","nullOrUrl","variableFromParentCalledViaParent","variableFromOtherMethod","variableFromParent","varFromVariable","overwritting","parentOverwritting","calledParentOverwritting","calledParentOverwritted","calledParentSecondOverwritting","encapsedVariable","fromRenderDefault"] ["parentForm","header","footer","onlyParentDefaultForm"]',
            'TEMPLATE other.latte VariablesPresenter::other ["startup","startupParent","presenter","control","flashes","fromOtherAction","unresolvedInclude"] ["parentForm","header","footer"]',
            'TEMPLATE parent.latte VariablesPresenter::parent ["startup","startupParent","presenter","control","flashes","variableFromParentAction","variableFromOtherMethod"] ["parentForm","header","footer","parentDefaultForm"]',
            'TEMPLATE specialConstructs.latte VariablesPresenter:: ["startup","startupParent","presenter","control","flashes"] ["parentForm","header","footer"]',
            'TEMPLATE noAction.latte VariablesPresenter:: ["startup","startupParent","presenter","control","flashes"] ["parentForm","header","footer"]',
            'TEMPLATE direct.latte VariablesPresenter::directRender ["startup","startupParent","presenter","control","flashes","fromTemplate","fromRender"] ["parentForm","header","footer"]',
            'TEMPLATE dynamicInclude.latte VariablesPresenter::dynamicInclude ["startup","startupParent","presenter","control","flashes","dynamicIncludeVar","includedTemplate"] ["parentForm","header","footer"]',
            'TEMPLATE onlyRender.latte VariablesPresenter::onlyRender ["startup","startupParent","presenter","control","flashes","fromOnlyRender"] ["parentForm","header","footer"]',
            'TEMPLATE different.latte VariablesPresenter::different ["startup","startupParent","presenter","control","flashes","fromDifferentRender"] ["parentForm","header","footer"]',
            'TEMPLATE different2.latte VariablesPresenter::different2 ["startup","startupParent","presenter","control","flashes","fromDifferentRender2"] ["parentForm","header","footer"]',
            'TEMPLATE different.latte VariablesPresenter::differentRender(different) ["startup","startupParent","presenter","control","flashes","fromDifferentRenderAction","fromDifferentRender"] ["parentForm","header","footer"]',
            'TEMPLATE different.latte VariablesPresenter::differentRenders(different) ["startup","startupParent","presenter","control","flashes","fromDifferentRendersAction","fromDifferentRender"] ["parentForm","header","footer"]',
            'TEMPLATE different2.latte VariablesPresenter::differentRenders(different2) ["startup","startupParent","presenter","control","flashes","fromDifferentRendersAction","fromDifferentRender2"] ["parentForm","header","footer"]',
            'TEMPLATE differentRenderConditional.latte VariablesPresenter::differentRenderConditional ["startup","startupParent","presenter","control","flashes"] ["parentForm","header","footer"]',
            'TEMPLATE different.latte VariablesPresenter::differentRenderIndirect(different) ["startup","startupParent","presenter","control","flashes","fromDifferentRender"] ["parentForm","header","footer"]',
            'TEMPLATE different.latte VariablesPresenter::differentRenderConditional(different) ["startup","startupParent","presenter","control","flashes","fromDifferentRender"] ["parentForm","header","footer"]',
            'TEMPLATE @layout.latte VariablesPresenter::arrayShapeParams ["startup","startupParent","presenter","control","flashes","a","b","c"] ["parentForm","header","footer"]',
            'TEMPLATE @layout.latte VariablesPresenter::objectShapeParams ["startup","startupParent","presenter","control","flashes","objectShape"] ["parentForm","header","footer"]',
            'TEMPLATE @layout.latte VariablesPresenter::default ["startup","startupParent","presenter","control","flashes","title","viaGetTemplate","stringLists","localStrings","dynamic","obj","array1","array2","list1","list2","array1WithoutType","array2WithoutType","list1WithType","list2WithType","variableFromMethodCallOnVariable","someOtherVariableWithDefault","nullOrUrl","variableFromParentCalledViaParent","variableFromOtherMethod","variableFromParent","varFromVariable","overwritting","parentOverwritting","calledParentOverwritting","calledParentOverwritted","calledParentSecondOverwritting","encapsedVariable","fromRenderDefault"] ["parentForm","header","footer","onlyParentDefaultForm"]',
            'TEMPLATE @layoutOther.latte VariablesPresenter::other ["startup","startupParent","presenter","control","flashes","fromOtherAction","unresolvedInclude"] ["parentForm","header","footer"]',
            'TEMPLATE @layout.latte VariablesPresenter::parent ["startup","startupParent","presenter","control","flashes","variableFromParentAction","variableFromOtherMethod"] ["parentForm","header","footer","parentDefaultForm"]',
            'TEMPLATE @layout.latte VariablesPresenter:: ["startup","startupParent","presenter","control","flashes"] ["parentForm","header","footer"]',
            'TEMPLATE @layout.latte VariablesPresenter::directRender ["startup","startupParent","presenter","control","flashes","fromTemplate","fromRender"] ["parentForm","header","footer"]',
            'TEMPLATE @layout.latte VariablesPresenter::dynamicInclude ["startup","startupParent","presenter","control","flashes","dynamicIncludeVar","includedTemplate"] ["parentForm","header","footer"]',
            'TEMPLATE @layout.latte VariablesPresenter::onlyRender ["startup","startupParent","presenter","control","flashes","fromOnlyRender"] ["parentForm","header","footer"]',
            'TEMPLATE @layout.latte VariablesPresenter::different ["startup","startupParent","presenter","control","flashes","fromDifferentRender"] ["parentForm","header","footer"]',
            'TEMPLATE @layout.latte VariablesPresenter::different2 ["startup","startupParent","presenter","control","flashes","fromDifferentRender2"] ["parentForm","header","footer"]',
            'TEMPLATE @layout.latte VariablesPresenter::differentRender(different) ["startup","startupParent","presenter","control","flashes","fromDifferentRenderAction","fromDifferentRender"] ["parentForm","header","footer"]',
            'TEMPLATE @layout.latte VariablesPresenter::differentRenders(different) ["startup","startupParent","presenter","control","flashes","fromDifferentRendersAction","fromDifferentRender"] ["parentForm","header","footer"]',
            'TEMPLATE @layout.latte VariablesPresenter::differentRenders(different2) ["startup","startupParent","presenter","control","flashes","fromDifferentRendersAction","fromDifferentRender2"] ["parentForm","header","footer"]',
            'TEMPLATE @layout.latte VariablesPresenter::differentRenderConditional ["startup","startupParent","presenter","control","flashes"] ["parentForm","header","footer"]',
            'TEMPLATE @layout.latte VariablesPresenter::differentRenderIndirect(different) ["startup","startupParent","presenter","control","flashes","fromDifferentRender"] ["parentForm","header","footer"]',
            'TEMPLATE @layout.latte VariablesPresenter::differentRenderConditional(different) ["startup","startupParent","presenter","control","flashes","fromDifferentRender"] ["parentForm","header","footer"]',
            'NODE NetteApplicationUIPresenter {"className":"ParentPresenter"}',
            'NODE NetteApplicationUIPresenterStandalone {"className":"ParentPresenter"}',
        ], __NAMESPACE__ . '\Fixtures');
    }

    public function testComponents(): void
    {
        $this->analyse([
            __DIR__ . '/Fixtures/ComponentsPresenter.php',
            __DIR__ . '/Fixtures/ParentPresenter.php',
            __DIR__ . '/Source/ControlRegistrator.php',
            __DIR__ . '/Source/SomeBodyControl.php',
            __DIR__ . '/Source/SomeControl.php',
            __DIR__ . '/Source/SomeFooterControl.php',
            __DIR__ . '/Source/SomeHeaderControl.php',
            __DIR__ . '/Source/SomeTableControl.php',
        ], [
            'NODE NetteApplicationUIPresenter {"className":"ComponentsPresenter"}',
            'TEMPLATE create.latte ComponentsPresenter::create ["startupParent","presenter","control","flashes"] ["parentForm","header","footer","form","noType","implicitType","multiplier","onlyCreateForm"]',
            'TEMPLATE default.latte ComponentsPresenter::default ["startupParent","presenter","control","flashes","varControl","variableFromParentCalledViaParent"] ["parentForm","header","footer","form","noType","implicitType","multiplier","onlyParentDefaultForm","someControl","someUnionControl"]',
            'TEMPLATE noAction.latte ComponentsPresenter:: ["startupParent","presenter","control","flashes"] ["parentForm","header","footer","form","noType","implicitType","multiplier"]',
            'TEMPLATE parent.latte ComponentsPresenter::parent ["startupParent","presenter","control","flashes","variableFromParentAction"] ["parentForm","header","footer","form","noType","implicitType","multiplier","parentDefaultForm"]',
            'TEMPLATE @layout.latte ComponentsPresenter::create ["startupParent","presenter","control","flashes"] ["parentForm","header","footer","form","noType","implicitType","multiplier","onlyCreateForm"]',
            'TEMPLATE @layout.latte ComponentsPresenter::default ["startupParent","presenter","control","flashes","varControl","variableFromParentCalledViaParent"] ["parentForm","header","footer","form","noType","implicitType","multiplier","onlyParentDefaultForm","someControl","someUnionControl"]',
            'TEMPLATE @layout.latte ComponentsPresenter:: ["startupParent","presenter","control","flashes"] ["parentForm","header","footer","form","noType","implicitType","multiplier"]',
            'TEMPLATE @layout.latte ComponentsPresenter::parent ["startupParent","presenter","control","flashes","variableFromParentAction"] ["parentForm","header","footer","form","noType","implicitType","multiplier","parentDefaultForm"]',
            'NODE NetteApplicationUIPresenterStandalone {"className":"ComponentsPresenter"}',
            'NODE NetteApplicationUIPresenter {"className":"ParentPresenter"}',
            'NODE NetteApplicationUIPresenterStandalone {"className":"ParentPresenter"}',
            'NODE NetteApplicationUIControl {"className":"SomeBodyControl"}',
            'NODE NetteApplicationUIControl {"className":"SomeControl"}',
            'NODE NetteApplicationUIControl {"className":"SomeFooterControl"}',
            'NODE NetteApplicationUIControl {"className":"SomeHeaderControl"}',
            'NODE NetteApplicationUIControl {"className":"SomeTableControl"}',
            'TEMPLATE control.latte SomeBodyControl::render ["presenter","control","flashes"] ["table"]',
            'TEMPLATE SomeControl.latte SomeControl::render ["presenter","control","flashes"] ["body","footer","header"]',
            'TEMPLATE SomeControl.latte SomeControl::renderOtherRender ["presenter","control","flashes"] ["body","footer","header"]',
            'TEMPLATE SomeControl.latte SomeControl::renderAnotherRender ["presenter","control","flashes"] ["body","footer","header"]',
            'TEMPLATE control.latte SomeFooterControl::render ["presenter","control","flashes"] []',
            'TEMPLATE control.latte SomeHeaderControl::render ["presenter","control","flashes"] []',
            'TEMPLATE control.latte SomeTableControl::render ["presenter","control","flashes"] []',
        ]);
    }

    public function testFilters(): void
    {
        $this->analyse([__DIR__ . '/Fixtures/FiltersPresenter.php', __DIR__ . '/Fixtures/ParentPresenter.php'], [
            'NODE NetteApplicationUIPresenter {"className":"FiltersPresenter"}',
            'TEMPLATE default.latte FiltersPresenter::default ["startupParent","presenter","control","flashes","title","subtitle","someObject"] ["parentForm","header","footer"]',
            'TEMPLATE parent.latte FiltersPresenter::parent ["startupParent","presenter","control","flashes","variableFromParentAction"] ["parentForm","header","footer","parentDefaultForm"]',
            'TEMPLATE translate_new.latte FiltersPresenter:: ["startupParent","presenter","control","flashes"] ["parentForm","header","footer"]',
            'TEMPLATE @layout.latte FiltersPresenter::default ["startupParent","presenter","control","flashes","title","subtitle","someObject"] ["parentForm","header","footer"]',
            'TEMPLATE @layout.latte FiltersPresenter::parent ["startupParent","presenter","control","flashes","variableFromParentAction"] ["parentForm","header","footer","parentDefaultForm"]',
            'TEMPLATE @layout.latte FiltersPresenter:: ["startupParent","presenter","control","flashes"] ["parentForm","header","footer"]',
            'NODE NetteApplicationUIPresenter {"className":"ParentPresenter"}',
            'NODE NetteApplicationUIPresenterStandalone {"className":"FiltersPresenter"}',
            'NODE NetteApplicationUIPresenterStandalone {"className":"ParentPresenter"}',
        ]);
    }

    public function testLinks(): void
    {
        $this->analyse([__DIR__ . '/Fixtures/LinksPresenter.php', __DIR__ . '/Fixtures/ParentPresenter.php'], [
            'NODE NetteApplicationUIPresenter {"className":"LinksPresenter"}',
            'TEMPLATE default.latte LinksPresenter::default ["startupParent","presenter","control","flashes"] ["parentForm","header","footer"]',
            'TEMPLATE create.latte LinksPresenter::create ["startupParent","presenter","control","flashes"] ["parentForm","header","footer"]',
            'TEMPLATE edit.latte LinksPresenter::edit ["startupParent","presenter","control","flashes"] ["parentForm","header","footer"]',
            'TEMPLATE publish.latte LinksPresenter::publish ["startupParent","presenter","control","flashes"] ["parentForm","header","footer"]',
            'TEMPLATE paramsMismatch.latte LinksPresenter::paramsMismatch ["startupParent","presenter","control","flashes"] ["parentForm","header","footer"]',
            'TEMPLATE arrayParam.latte LinksPresenter::arrayParam ["startupParent","presenter","control","flashes"] ["parentForm","header","footer"]',
            'TEMPLATE parent.latte LinksPresenter::parent ["startupParent","presenter","control","flashes","variableFromParentAction"] ["parentForm","header","footer","parentDefaultForm"]',
            'TEMPLATE @layout.latte LinksPresenter::default ["startupParent","presenter","control","flashes"] ["parentForm","header","footer"]',
            'TEMPLATE @layout.latte LinksPresenter::create ["startupParent","presenter","control","flashes"] ["parentForm","header","footer"]',
            'TEMPLATE @layout.latte LinksPresenter::edit ["startupParent","presenter","control","flashes"] ["parentForm","header","footer"]',
            'TEMPLATE @layout.latte LinksPresenter::publish ["startupParent","presenter","control","flashes"] ["parentForm","header","footer"]',
            'TEMPLATE @layout.latte LinksPresenter::paramsMismatch ["startupParent","presenter","control","flashes"] ["parentForm","header","footer"]',
            'TEMPLATE @layout.latte LinksPresenter::arrayParam ["startupParent","presenter","control","flashes"] ["parentForm","header","footer"]',
            'TEMPLATE @layout.latte LinksPresenter::parent ["startupParent","presenter","control","flashes","variableFromParentAction"] ["parentForm","header","footer","parentDefaultForm"]',
            'NODE NetteApplicationUIPresenter {"className":"ParentPresenter"}',
            'NODE NetteApplicationUIPresenterStandalone {"className":"LinksPresenter"}',
            'NODE NetteApplicationUIPresenterStandalone {"className":"ParentPresenter"}',
        ]);
    }

    public function testResolve(): void
    {
        $this->analyse([__DIR__ . '/Fixtures/ResolvePresenter.php'], [
            'NODE NetteApplicationUIPresenter {"className":"ResolvePresenter"}',
            'NODE NetteApplicationUIPresenterStandalone {"className":"ResolvePresenter"}',
            'TEMPLATE dieSometimes.latte ResolvePresenter::dieSometimes ["presenter","control","flashes"] []',
            'TEMPLATE empty.latte ResolvePresenter::empty ["presenter","control","flashes"] []',
            'TEMPLATE exitSometimes.latte ResolvePresenter::exitSometimes ["presenter","control","flashes"] []',
            'TEMPLATE throwSometimes.latte ResolvePresenter::throwSometimes ["presenter","control","flashes"] []',
            'TEMPLATE recursion.latte ResolvePresenter::recursion ["presenter","control","flashes","variableFromRecursionMethod"] []',
            'TEMPLATE setFile.changed.latte ResolvePresenter::setFile ["presenter","control","flashes"] []',
            'TEMPLATE sendTemplate.latte ResolvePresenter::sendTemplate ["presenter","control","flashes","send"] []',
            'TEMPLATE unanalysed.latte ResolvePresenter:: ["presenter","control","flashes"] []',
            'TEMPLATE @layout.latte ResolvePresenter::dieSometimes ["presenter","control","flashes"] []',
            'TEMPLATE @layout.latte ResolvePresenter::empty ["presenter","control","flashes"] []',
            'TEMPLATE @layout.latte ResolvePresenter::exitSometimes ["presenter","control","flashes"] []',
            'TEMPLATE @layout.latte ResolvePresenter::throwSometimes ["presenter","control","flashes"] []',
            'TEMPLATE @layout.latte ResolvePresenter::recursion ["presenter","control","flashes","variableFromRecursionMethod"] []',
            'TEMPLATE @layout.latte ResolvePresenter::setFile ["presenter","control","flashes"] []',
            'TEMPLATE @layout.latte ResolvePresenter::sendTemplate ["presenter","control","flashes","send"] []',
            'TEMPLATE @layout.latte ResolvePresenter:: ["presenter","control","flashes"] []',
        ]);
    }

    public function testStartupView(): void
    {
        $this->analyse([__DIR__ . '/Fixtures/StartupViewPresenter.php'], [
            'NODE NetteApplicationUIPresenter {"className":"StartupViewPresenter"}',
            'NODE NetteApplicationUIPresenterStandalone {"className":"StartupViewPresenter"}',
            'TEMPLATE default.latte StartupViewPresenter::default ["startupParent","presenter","control","flashes","fromDefault","fromRenderDefault"] ["parentForm","header","footer"]',
            'TEMPLATE parent.latte StartupViewPresenter::parent ["startupParent","presenter","control","flashes","variableFromParentAction"] ["parentForm","header","footer","parentDefaultForm"]',
            'TEMPLATE startup.latte StartupViewPresenter::default(startup) ["startupParent","presenter","control","flashes","fromDefault","fromRenderStartup"] ["parentForm","header","footer"]',
            'TEMPLATE startup.latte StartupViewPresenter::parent(startup) ["startupParent","presenter","control","flashes","variableFromParentAction","fromRenderStartup"] ["parentForm","header","footer","parentDefaultForm"]',
            'TEMPLATE startup.latte StartupViewPresenter::startup ["startupParent","presenter","control","flashes","fromRenderStartup"] ["parentForm","header","footer"]',
            'TEMPLATE @layout.latte StartupViewPresenter::default ["startupParent","presenter","control","flashes","fromDefault","fromRenderDefault"] ["parentForm","header","footer"]',
            'TEMPLATE @layout.latte StartupViewPresenter::parent ["startupParent","presenter","control","flashes","variableFromParentAction"] ["parentForm","header","footer","parentDefaultForm"]',
            'TEMPLATE @layout.latte StartupViewPresenter::default(startup) ["startupParent","presenter","control","flashes","fromDefault","fromRenderStartup"] ["parentForm","header","footer"]',
            'TEMPLATE @layout.latte StartupViewPresenter::parent(startup) ["startupParent","presenter","control","flashes","variableFromParentAction","fromRenderStartup"] ["parentForm","header","footer","parentDefaultForm"]',
            'TEMPLATE @layout.latte StartupViewPresenter::startup ["startupParent","presenter","control","flashes","fromRenderStartup"] ["parentForm","header","footer"]',
        ]);
    }
}
