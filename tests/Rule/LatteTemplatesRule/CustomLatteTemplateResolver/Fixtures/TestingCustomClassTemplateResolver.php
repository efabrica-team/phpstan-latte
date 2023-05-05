<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Tests\Rule\LatteTemplatesRule\CustomLatteTemplateResolver\Fixtures;

use Efabrica\PHPStanLatte\LatteContext\LatteContext;
use Efabrica\PHPStanLatte\LatteTemplateResolver\AbstractClassTemplateResolver;
use Efabrica\PHPStanLatte\LatteTemplateResolver\LatteTemplateResolverResult;
use Efabrica\PHPStanLatte\Template\Template;
use Efabrica\PHPStanLatte\Template\TemplateContext;
use Efabrica\PHPStanLatte\Template\Variable;
use Nette\Application\UI\Control;
use PHPStan\BetterReflection\Reflection\ReflectionClass;
use PHPStan\Type\StringType;

final class TestingCustomClassTemplateResolver extends AbstractClassTemplateResolver
{
    protected function getSupportedClasses(): array
    {
        return ['object'];
    }

    protected function getClassNamePattern(): string
    {
        return '/.*Control/';
    }

    protected function getClassResult(ReflectionClass $resolveClass, LatteContext $latteContext): LatteTemplateResolverResult
    {
        $result = new LatteTemplateResolverResult();
        $result->addTemplate(new Template(
            dirname($resolveClass->getFileName()) . '/templates/default.latte',
            Control::class,
            'resolved',
            new TemplateContext(
                [new Variable('classVariable', new StringType())],
            ),
        ));
        return $result;
    }
}
