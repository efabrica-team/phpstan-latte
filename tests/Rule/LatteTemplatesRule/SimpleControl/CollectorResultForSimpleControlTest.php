<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Tests\Rule\LatteTemplatesRule\PresenterWithoutModule;

use Efabrica\PHPStanLatte\Tests\Rule\LatteTemplatesRule\CollectorResultTest;

final class CollectorResultForSimpleControlTest extends CollectorResultTest
{
    public static function getAdditionalConfigFiles(): array
    {
        return [
            __DIR__ . '/../../../../rules.neon',
            __DIR__ . '/../../../config.neon',
            __DIR__ . '/config.neon',
        ];
    }

    public function testThisTemplate(): void
    {
        $this->analyse([__DIR__ . '/Fixtures/ThisTemplate/SomeControl.php'], [
            'NODE NetteApplicationUIControl {"className":"SomeControl"}',
            'TEMPLATE default.latte SomeControl::render ["presenter","control","a","b"] []',

        ]);
    }

    public function testThisGetTemplate(): void
    {
        $this->analyse([__DIR__ . '/Fixtures/ThisGetTemplate/SomeControl.php'], [
            'NODE NetteApplicationUIControl {"className":"SomeControl"}',
            'TEMPLATE default.latte SomeControl::render ["presenter","control","a","b"] []',
        ]);
    }

    public function testTemplateAsVariable(): void
    {
        $this->analyse([__DIR__ . '/Fixtures/TemplateAsVariable/SomeControl.php'], [
            'NODE NetteApplicationUIControl {"className":"SomeControl"}',
            'TEMPLATE default.latte SomeControl::render ["presenter","control","a","b"] []',
        ]);
    }

    public function testMultipleRenderMethods(): void
    {
        $this->analyse([__DIR__ . '/Fixtures/MultipleRenderMethods/SomeControl.php'], [
            'NODE NetteApplicationUIControl {"className":"SomeControl"}',
            'TEMPLATE default.latte SomeControl::render ["presenter","control","a","b"] []',
            'TEMPLATE test.latte SomeControl::renderTest ["presenter","control","c","d"] []',
            'TEMPLATE invalid_file.latte SomeControl::renderTemplateFileNotFound ["presenter","control"] []',
            'TEMPLATE param_a.latte SomeControl::renderWildcard ["presenter","control","a","c"] []',
            'TEMPLATE param_b.latte SomeControl::renderWildcard ["presenter","control","a","c"] []',
        ]);
    }

    public function testResolveError(): void
    {
        $this->analyse([__DIR__ . '/Fixtures/ResolveError/SomeControl.php'], [
            'NODE NetteApplicationUIControl {"className":"SomeControl"}',
        ]);
    }

    public function testResolve(): void
    {
        $this->analyse([__DIR__ . '/Fixtures/Resolve/SomeControl.php'], [
            'NODE NetteApplicationUIControl {"className":"SomeControl"}',
            'TEMPLATE constVar.latte SomeControl::renderConstVar ["presenter","control","a","b"] []',
            'TEMPLATE explicit.latte SomeControl::renderExplicit ["presenter","control","a","b"] []',
            'TEMPLATE defaultVars.latte SomeControl::renderDefaultVars ["presenter","control","a","b"] []',
            'TEMPLATE explicitVars.latte SomeControl::renderExplicitVars ["presenter","control","a","b"] []',
            'TEMPLATE defaultObject.latte SomeControl::renderDefaultObject ["presenter","control","a","b"] []',
            'TEMPLATE explicitObject.latte SomeControl::renderExplicitObject ["presenter","control","a","b"] []',
            'TEMPLATE complexType.latte SomeControl::renderComplexType ["presenter","control","a","b"] []',
            'TEMPLATE throwSometimes.latte SomeControl::renderThrowSometimes ["presenter","control"] []',
            'TEMPLATE default.latte SomeControl::renderIgnoredRender ["presenter","control"] []',
            'TEMPLATE methodCall.latte SomeControl::renderMethodCall ["presenter","control"] []',
            'TEMPLATE methodCall.latte SomeControl::renderMethodCallPart ["presenter","control"] []',
            'TEMPLATE methodCall.latte SomeControl::renderStaticMethodCall ["presenter","control"] []',
            'TEMPLATE methodCall.aa.latte SomeControl::renderMethodCallMulti ["presenter","control"] []',
            'TEMPLATE methodCall.ab.latte SomeControl::renderMethodCallMulti ["presenter","control"] []',
            'TEMPLATE methodCall.ba.latte SomeControl::renderMethodCallMulti ["presenter","control"] []',
            'TEMPLATE methodCall.bb.latte SomeControl::renderMethodCallMulti ["presenter","control"] []',
        ]);
    }

    public function testHierarchy(): void
    {
        $this->analyse([__DIR__ . '/Fixtures/Hierarchy/SomeControl.php'], [
            'NODE NetteApplicationUIControl {"className":"SomeControl"}',
            'TEMPLATE default.latte SomeControl::render ["presenter","control","some","parent","grandParent","data","parentData","grandParentData"] []',
            'TEMPLATE parent.latte SomeControl::renderParent ["presenter","control","parent","grandParent","data","parentData","grandParentData"] []',
            'TEMPLATE grandParent.latte SomeControl::renderGrandParent ["presenter","control","grandParent","data","parentData","grandParentData"] []',
        ]);
    }
}
