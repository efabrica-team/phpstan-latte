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
use PHPStan\Reflection\ClassReflection;
use PHPStan\Type\StringType;
use function dirname;

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

    protected function getClassResult(ClassReflection $classReflection, LatteContext $latteContext): LatteTemplateResolverResult
    {
        $result = new LatteTemplateResolverResult();
        $result->addTemplate(new Template(
            dirname($classReflection->getFileName()) . '/templates/default.latte',
            Control::class,
            'resolved',
            new TemplateContext(
                [new Variable('classVariable', new StringType())],
            ),
        ));
        return $result;
    }
}
