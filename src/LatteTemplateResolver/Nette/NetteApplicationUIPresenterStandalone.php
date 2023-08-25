<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\LatteTemplateResolver\Nette;

use Efabrica\PHPStanLatte\LatteContext\LatteContext;
use Efabrica\PHPStanLatte\LatteContext\Resolver\LatteContextResolverInterface;
use Efabrica\PHPStanLatte\LatteContext\Resolver\Nette\NetteApplicationUIPresenterLatteContextResolver;
use Efabrica\PHPStanLatte\LatteTemplateResolver\AbstractClassStandaloneTemplateResolver;
use PHPStan\BetterReflection\Reflection\ReflectionClass;

final class NetteApplicationUIPresenterStandalone extends AbstractClassStandaloneTemplateResolver
{
    public function getSupportedClasses(): array
    {
        return ['Nette\Application\UI\Presenter'];
    }

    protected function getClassContextResolver(ReflectionClass $reflectionClass, LatteContext $latteContext): LatteContextResolverInterface
    {
        return new NetteApplicationUIPresenterLatteContextResolver($reflectionClass, $latteContext);
    }

    protected function getTemplatePathPatterns(ReflectionClass $reflectionClass, string $dir): array
    {
        $shortClassName = $reflectionClass->getShortName();
        $presenterName = str_replace('Presenter', '', $shortClassName);

        return [
             $dir . '/templates/' . $presenterName . '/([a-zA-Z0-9_]+).latte',
             $dir . '/templates/' . $presenterName . '\.([a-zA-Z0-9_]+).latte',
        ];
    }

    protected function adjustDir(string $dir): string
    {
        return is_dir("$dir/templates") ? $dir : dirname($dir);
    }

    protected function isStandaloneTemplate(ReflectionClass $reflectionClass, string $templateFile, array $matches): bool
    {
        if (!is_string($matches[1])) {
            return false;
        }
        $action = $matches[1];
        return count($this->getMethodsMatchingIncludingIgnored($reflectionClass, '/^(action|render)' . preg_quote($action) . '/')) === 0;
    }
}
