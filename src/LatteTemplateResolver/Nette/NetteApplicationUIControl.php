<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\LatteTemplateResolver\Nette;

use Efabrica\PHPStanLatte\LatteContext\LatteContext;
use Efabrica\PHPStanLatte\LatteContext\Resolver\LatteContextResolverInterface;
use Efabrica\PHPStanLatte\LatteContext\Resolver\Nette\NetteApplicationUIControlLatteContextResolver;
use Efabrica\PHPStanLatte\LatteTemplateResolver\AbstractClassMethodTemplateResolver;
use PHPStan\BetterReflection\Reflection\ReflectionClass;

final class NetteApplicationUIControl extends AbstractClassMethodTemplateResolver
{
    public function getSupportedClasses(): array
    {
        return ['Nette\Application\UI\Control'];
    }

    public function getIgnoredClasses(): array
    {
        return ['Nette\Application\UI\Presenter'];
    }

    protected function getClassMethodPattern(): string
    {
        return '/^render.*/';
    }

    protected function getClassContextResolver(ReflectionClass $reflectionClass, LatteContext $latteContext): LatteContextResolverInterface
    {
        return new NetteApplicationUIControlLatteContextResolver($reflectionClass, $latteContext);
    }
}
