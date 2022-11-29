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
            'NODE NetteApplicationUIPresenter {"className":"\PresenterWithoutModule\Fixtures\VariablesPresenter"}',
            'TEMPLATE default.latte VariablesPresenter ["startup","startupParent","actualClass","presenter","title","viaGetTemplate","variableFromParentCalledViaParent","variableFromParent","varFromVariable","variableFromOtherMethod","variableFromRecursionMethod","fromRenderDefault"] ["parentForm","parentForm","onlyParentDefaultForm"]',
            'TEMPLATE other.latte VariablesPresenter ["startup","startupParent","actualClass","presenter","fromOtherAction"] ["parentForm"]',
            'TEMPLATE empty.latte VariablesPresenter ["startup","startupParent","actualClass","presenter"] ["parentForm"]',
            'TEMPLATE parent.latte VariablesPresenter ["startup","startupParent","actualClass","presenter","variableFromParentAction"] ["parentForm","parentDefaultForm"]',
            'TEMPLATE noAction.latte VariablesPresenter ["startup","startupParent","actualClass","presenter"] ["parentForm"]',
            'NODE NetteApplicationUIPresenter {"className":"\PresenterWithoutModule\Fixtures\ParentPresenter"}',
            'NODE NetteApplicationUIControl {"className":"\PresenterWithoutModule\Fixtures\VariablesPresenter"}',
            'NODE NetteApplicationUIControl {"className":"\PresenterWithoutModule\Fixtures\ParentPresenter"}',
        ]);
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
            'NODE NetteApplicationUIPresenter {"className":"\PresenterWithoutModule\Fixtures\ComponentsPresenter"}',
            'TEMPLATE default.latte ComponentsPresenter ["startupParent","actualClass","presenter","variableFromParentCalledViaParent"] ["form","parentForm","parentForm","onlyParentDefaultForm","someControl"]',
            'TEMPLATE create.latte ComponentsPresenter ["startupParent","actualClass","presenter"] ["form","parentForm","onlyCreateForm"]',
            'TEMPLATE parent.latte ComponentsPresenter ["startupParent","actualClass","presenter","variableFromParentAction"] ["form","parentForm","parentDefaultForm"]',
            'TEMPLATE noAction.latte ComponentsPresenter ["startupParent","actualClass","presenter"] ["form","parentForm"]',
            'NODE NetteApplicationUIPresenter {"className":"\PresenterWithoutModule\Fixtures\ParentPresenter"}',
            'NODE NetteApplicationUIControl {"className":"\PresenterWithoutModule\Fixtures\ComponentsPresenter"}',
            'NODE NetteApplicationUIControl {"className":"\PresenterWithoutModule\Fixtures\ParentPresenter"}',
            'NODE NetteApplicationUIControl {"className":"\PresenterWithoutModule\Source\SomeBodyControl"}',
            'NODE NetteApplicationUIControl {"className":"\PresenterWithoutModule\Source\SomeControl"}',
            'NODE NetteApplicationUIControl {"className":"\PresenterWithoutModule\Source\SomeFooterControl"}',
            'NODE NetteApplicationUIControl {"className":"\PresenterWithoutModule\Source\SomeHeaderControl"}',
            'NODE NetteApplicationUIControl {"className":"\PresenterWithoutModule\Source\SomeTableControl"}',
        ]);
    }

    public function testFilters(): void
    {
        $this->analyse([__DIR__ . '/Fixtures/FiltersPresenter.php', __DIR__ . '/Fixtures/ParentPresenter.php'], [
            'NODE NetteApplicationUIPresenter {"className":"\PresenterWithoutModule\Fixtures\FiltersPresenter"}',
            'TEMPLATE default.latte FiltersPresenter ["actualClass","presenter","title"] []',
            'NODE NetteApplicationUIPresenter {"className":"\PresenterWithoutModule\Fixtures\ParentPresenter"}',
            'NODE NetteApplicationUIControl {"className":"\PresenterWithoutModule\Fixtures\FiltersPresenter"}',
            'NODE NetteApplicationUIControl {"className":"\PresenterWithoutModule\Fixtures\ParentPresenter"}',
        ]);
    }

    public function testLinks(): void
    {
        $this->analyse([__DIR__ . '/Fixtures/LinksPresenter.php', __DIR__ . '/Fixtures/ParentPresenter.php'], [
            'NODE NetteApplicationUIPresenter {"className":"\PresenterWithoutModule\Fixtures\LinksPresenter"}',
            'TEMPLATE default.latte LinksPresenter ["actualClass","presenter"] []',
            'NODE NetteApplicationUIPresenter {"className":"\PresenterWithoutModule\Fixtures\ParentPresenter"}',
            'NODE NetteApplicationUIControl {"className":"\PresenterWithoutModule\Fixtures\LinksPresenter"}',
            'NODE NetteApplicationUIControl {"className":"\PresenterWithoutModule\Fixtures\ParentPresenter"}',
        ]);
    }
}
