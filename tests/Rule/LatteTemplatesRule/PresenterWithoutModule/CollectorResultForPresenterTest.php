<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Tests\Rule\LatteTemplatesRule\PresenterWithoutModule;

use Efabrica\PHPStanLatte\Tests\Rule\LatteTemplatesRule\CollectorResultTest;

final class CollectorResultForPresenterTest extends CollectorResultTest
{
    public static function getAdditionalConfigFiles(): array
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
            'TEMPLATE default.latte VariablesPresenter::default ["startup","startupParent","presenter","control","title","viaGetTemplate","stringLists","localStrings","dynamic","obj","array1","array2","list1","list2","array1WithoutType","array2WithoutType","list1WithType","list2WithType","variableFromParentCalledViaParent","variableFromOtherMethod","variableFromParent","varFromVariable","overwritting","parentOverwritting","calledParentOverwritting","calledParentOverwritted","calledParentSecondOverwritting","fromRenderDefault"] ["parentForm","onlyParentDefaultForm"]',
            'TEMPLATE other.latte VariablesPresenter::other ["startup","startupParent","presenter","control","fromOtherAction"] ["parentForm"]',
            'TEMPLATE parent.latte VariablesPresenter::parent ["startup","startupParent","presenter","control","variableFromParentAction","variableFromOtherMethod"] ["parentForm","parentDefaultForm"]',
            'TEMPLATE noAction.latte VariablesPresenter:: ["startup","startupParent","presenter","control"] ["parentForm"]',
            'TEMPLATE direct.latte VariablesPresenter::directRender ["startup","startupParent","presenter","control","fromTemplate","fromRender"] ["parentForm"]',
            'TEMPLATE dynamicInclude.latte VariablesPresenter::dynamicInclude ["startup","startupParent","presenter","control","dynamicIncludeVar","includedTemplate"] ["parentForm"]',
            'TEMPLATE onlyRender.latte VariablesPresenter::onlyRender ["startup","startupParent","presenter","control","fromOnlyRender"] ["parentForm"]',
            'TEMPLATE different.latte VariablesPresenter::different ["startup","startupParent","presenter","control","fromDifferentRender"] ["parentForm"]',
            'TEMPLATE different2.latte VariablesPresenter::different2 ["startup","startupParent","presenter","control","fromDifferentRender2"] ["parentForm"]',
            'TEMPLATE different.latte VariablesPresenter::differentRender(different) ["startup","startupParent","presenter","control","fromDifferentRenderAction","fromDifferentRender"] ["parentForm"]',
            'TEMPLATE different.latte VariablesPresenter::differentRenders(different) ["startup","startupParent","presenter","control","fromDifferentRendersAction","fromDifferentRender"] ["parentForm"]',
            'TEMPLATE different2.latte VariablesPresenter::differentRenders(different2) ["startup","startupParent","presenter","control","fromDifferentRendersAction","fromDifferentRender2"] ["parentForm"]',
            'TEMPLATE differentRenderConditional.latte VariablesPresenter::differentRenderConditional ["startup","startupParent","presenter","control"] ["parentForm"]',
            'TEMPLATE different.latte VariablesPresenter::differentRenderConditional(different) ["startup","startupParent","presenter","control","fromDifferentRender"] ["parentForm"]',
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
            'TEMPLATE create.latte ComponentsPresenter::create ["startupParent","presenter","control"] ["parentForm","form","noType","implicitType","onlyCreateForm"]',
            'TEMPLATE default.latte ComponentsPresenter::default ["startupParent","presenter","control","variableFromParentCalledViaParent"] ["parentForm","form","noType","implicitType","onlyParentDefaultForm","someControl"]',
            'TEMPLATE noAction.latte ComponentsPresenter:: ["startupParent","presenter","control"] ["parentForm","form","noType","implicitType"]',
            'TEMPLATE parent.latte ComponentsPresenter::parent ["startupParent","presenter","control","variableFromParentAction"] ["parentForm","form","noType","implicitType","parentDefaultForm"]',
            'NODE NetteApplicationUIPresenterStandalone {"className":"ComponentsPresenter"}',
            'NODE NetteApplicationUIPresenter {"className":"ParentPresenter"}',
            'NODE NetteApplicationUIPresenterStandalone {"className":"ParentPresenter"}',
            'NODE NetteApplicationUIControl {"className":"SomeBodyControl"}',
            'NODE NetteApplicationUIControl {"className":"SomeControl"}',
            'NODE NetteApplicationUIControl {"className":"SomeFooterControl"}',
            'NODE NetteApplicationUIControl {"className":"SomeHeaderControl"}',
            'NODE NetteApplicationUIControl {"className":"SomeTableControl"}',
            'TEMPLATE control.latte SomeBodyControl::render ["presenter","control"] ["table"]',
            'TEMPLATE SomeControl.latte SomeControl::render ["presenter","control"] ["body","footer","header"]',
            'TEMPLATE SomeControl.latte SomeControl::renderOtherRender ["presenter","control"] ["body","footer","header"]',
            'TEMPLATE SomeControl.latte SomeControl::renderAnotherRender ["presenter","control"] ["body","footer","header"]',
            'TEMPLATE control.latte SomeFooterControl::render ["presenter","control"] []',
            'TEMPLATE control.latte SomeHeaderControl::render ["presenter","control"] []',
            'TEMPLATE control.latte SomeTableControl::render ["presenter","control"] []',
        ]);
    }

    public function testFilters(): void
    {
        $this->analyse([__DIR__ . '/Fixtures/FiltersPresenter.php', __DIR__ . '/Fixtures/ParentPresenter.php'], [
            'NODE NetteApplicationUIPresenter {"className":"FiltersPresenter"}',
            'TEMPLATE default.latte FiltersPresenter::default ["startupParent","presenter","control","title"] ["parentForm"]',
            'TEMPLATE parent.latte FiltersPresenter::parent ["startupParent","presenter","control","variableFromParentAction"] ["parentForm","parentDefaultForm"]',
            'NODE NetteApplicationUIPresenter {"className":"ParentPresenter"}',
            'NODE NetteApplicationUIPresenterStandalone {"className":"FiltersPresenter"}',
            'NODE NetteApplicationUIPresenterStandalone {"className":"ParentPresenter"}',
        ]);
    }

    public function testLinks(): void
    {
        $this->analyse([__DIR__ . '/Fixtures/LinksPresenter.php', __DIR__ . '/Fixtures/ParentPresenter.php'], [
            'NODE NetteApplicationUIPresenter {"className":"LinksPresenter"}',
            'TEMPLATE default.latte LinksPresenter::default ["startupParent","presenter","control"] ["parentForm"]',
            'TEMPLATE create.latte LinksPresenter::create ["startupParent","presenter","control"] ["parentForm"]',
            'TEMPLATE edit.latte LinksPresenter::edit ["startupParent","presenter","control"] ["parentForm"]',
            'TEMPLATE publish.latte LinksPresenter::publish ["startupParent","presenter","control"] ["parentForm"]',
            'TEMPLATE paramsMismatch.latte LinksPresenter::paramsMismatch ["startupParent","presenter","control"] ["parentForm"]',
            'TEMPLATE arrayParam.latte LinksPresenter::arrayParam ["startupParent","presenter","control"] ["parentForm"]',
            'TEMPLATE parent.latte LinksPresenter::parent ["startupParent","presenter","control","variableFromParentAction"] ["parentForm","parentDefaultForm"]',
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
            'TEMPLATE empty.latte ResolvePresenter::empty ["presenter","control"] []',
            'TEMPLATE throwSometimes.latte ResolvePresenter::throwSometimes ["presenter","control"] []',
            'TEMPLATE recursion.latte ResolvePresenter::recursion ["presenter","control","variableFromRecursionMethod"] []',
            'TEMPLATE setFile.changed.latte ResolvePresenter::setFile ["presenter","control"] []',
            'TEMPLATE sendTemplate.latte ResolvePresenter::sendTemplate ["presenter","control","send"] []',
        ]);
    }

    public function testStartupView(): void
    {
        $this->analyse([__DIR__ . '/Fixtures/StartupViewPresenter.php'], [
            'NODE NetteApplicationUIPresenter {"className":"StartupViewPresenter"}',
            'NODE NetteApplicationUIPresenterStandalone {"className":"StartupViewPresenter"}',
            'TEMPLATE default.latte StartupViewPresenter::default ["startupParent","presenter","control","fromDefault","fromRenderDefault"] ["parentForm"]',
            'TEMPLATE parent.latte StartupViewPresenter::parent ["startupParent","presenter","control","variableFromParentAction"] ["parentForm","parentDefaultForm"]',
            'TEMPLATE startup.latte StartupViewPresenter::default(startup) ["startupParent","presenter","control","fromDefault","fromRenderStartup"] ["parentForm"]',
            'TEMPLATE startup.latte StartupViewPresenter::parent(startup) ["startupParent","presenter","control","variableFromParentAction","fromRenderStartup"] ["parentForm","parentDefaultForm"]',
            'TEMPLATE startup.latte StartupViewPresenter::startup ["startupParent","presenter","control","fromRenderStartup"] ["parentForm"]',
        ]);
    }
}
