<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\LatteTemplateResolver;

final class NetteApplicationUIControl extends AbstractClassMethodTemplateResolver
{
    use NetteApplicationUIControlGlobals;

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
}
