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
        ];
    }

    public function testVariables(): void
    {
        $this->analyse([__DIR__ . '/Fixtures/VariablesPresenter.php', __DIR__ . '/Fixtures/ParentPresenter.php'], [
            'NODE NetteApplicationUIPresenter {"className":"VariablesPresenter"}',
            'NODE NetteApplicationUIPresenterStandalone {"className":"VariablesPresenter"}',
            'TEMPLATE default.latte VariablesPresenter::default ["startup","startupParent","presenter","control","title","viaGetTemplate","stringLists","localStrings","variableFromParentCalledViaParent","variableFromParent","varFromVariable","variableFromOtherMethod","fromRenderDefault"] ["parentForm","onlyParentDefaultForm"]',
            'TEMPLATE other.latte VariablesPresenter::other ["startup","startupParent","presenter","control","fromOtherAction"] ["parentForm"]',
            'TEMPLATE parent.latte VariablesPresenter::parent ["startup","startupParent","presenter","control","variableFromParentAction"] ["parentForm","parentDefaultForm"]',
            'TEMPLATE noAction.latte VariablesPresenter:: ["startup","startupParent","presenter","control"] ["parentForm"]',
            'TEMPLATE direct.latte VariablesPresenter::directRender ["startup","startupParent","presenter","control","fromTemplate","fromRender"] ["parentForm"]',
            'TEMPLATE dynamicInclude.latte VariablesPresenter::dynamicInclude ["startup","startupParent","presenter","control","dynamicIncludeVar","includedTemplate"] ["parentForm"]',
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
            'TEMPLATE default.latte ComponentsPresenter::default ["startupParent","presenter","control","variableFromParentCalledViaParent"] ["formFromTrait","form","noType","parentForm","onlyParentDefaultForm","someControl"]',
            'TEMPLATE create.latte ComponentsPresenter::create ["startupParent","presenter","control"] ["formFromTrait","form","noType","parentForm","onlyCreateForm"]',
            'TEMPLATE parent.latte ComponentsPresenter::parent ["startupParent","presenter","control","variableFromParentAction"] ["formFromTrait","form","noType","parentForm","parentDefaultForm"]',
            'TEMPLATE noAction.latte ComponentsPresenter:: ["startupParent","presenter","control"] ["formFromTrait","form","noType","parentForm"]',
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
            'TEMPLATE default.latte FiltersPresenter::default ["presenter","control","title"] ["parentForm"]',
            'TEMPLATE parent.latte FiltersPresenter::parent ["presenter","control","variableFromParentAction"] ["parentForm","parentDefaultForm"]',
            'NODE NetteApplicationUIPresenter {"className":"ParentPresenter"}',
            'NODE NetteApplicationUIPresenterStandalone {"className":"FiltersPresenter"}',
            'NODE NetteApplicationUIPresenterStandalone {"className":"ParentPresenter"}',
        ]);
    }

    public function testLinks(): void
    {
        $this->analyse([__DIR__ . '/Fixtures/LinksPresenter.php', __DIR__ . '/Fixtures/ParentPresenter.php'], [
            'NODE NetteApplicationUIPresenter {"className":"LinksPresenter"}',
            'TEMPLATE default.latte LinksPresenter::default ["presenter","control"] ["parentForm"]',
            'TEMPLATE create.latte LinksPresenter::create ["presenter","control"] ["parentForm"]',
            'TEMPLATE edit.latte LinksPresenter::edit ["presenter","control"] ["parentForm"]',
            'TEMPLATE publish.latte LinksPresenter::publish ["presenter","control"] ["parentForm"]',
            'TEMPLATE paramsMismatch.latte LinksPresenter::paramsMismatch ["presenter","control"] ["parentForm"]',
            'TEMPLATE arrayParam.latte LinksPresenter::arrayParam ["presenter","control"] ["parentForm"]',
            'TEMPLATE parent.latte LinksPresenter::parent ["presenter","control","variableFromParentAction"] ["parentForm","parentDefaultForm"]',
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
        ]);
    }
}
