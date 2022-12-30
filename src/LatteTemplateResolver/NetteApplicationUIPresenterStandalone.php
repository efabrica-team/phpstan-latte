<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\LatteTemplateResolver;

use PHPStan\BetterReflection\Reflection\ReflectionClass;

final class NetteApplicationUIPresenterStandalone extends AbstractClassStandaloneTemplateResolver
{
    use NetteApplicationUIPresenterGlobals;

    public function getSupportedClasses(): array
    {
        return ['Nette\Application\UI\Presenter'];
    }

    protected function getTemplatePathPatterns(ReflectionClass $reflectionClass, string $dir): array
    {
        $shortClassName = $reflectionClass->getShortName();
        $presenterName = str_replace('Presenter', '', $shortClassName);

        $dir = is_dir("$dir/templates") ? $dir : dirname($dir);

        return [
             $dir . '/templates/' . $presenterName . '/([a-zA-Z0-9_]*?).latte',
             $dir . '/templates/' . $presenterName . '\.([a-zA-Z0-9_]?).latte',
        ];
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
