<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\LatteTemplateResolver;

use Efabrica\PHPStanLatte\LatteContext\LatteContext;
use Efabrica\PHPStanLatte\PhpDoc\LattePhpDocResolver;
use Efabrica\PHPStanLatte\Resolver\LayoutResolver\LayoutPathResolver;
use Efabrica\PHPStanLatte\Template\Template;
use Nette\Utils\Finder;
use PHPStan\BetterReflection\Reflection\ReflectionClass;
use SplFileInfo;

abstract class AbstractClassStandaloneTemplateResolver extends AbstractClassTemplateResolver
{
    private LayoutPathResolver $layoutPathResolver;

    public function __construct(LattePhpDocResolver $lattePhpDocResolver, LayoutPathResolver $layoutPathResolver)
    {
        parent::__construct($lattePhpDocResolver);
        $this->layoutPathResolver = $layoutPathResolver;
    }

    protected function getClassResult(ReflectionClass $reflectionClass, LatteContext $latteContext): LatteTemplateResolverResult
    {
        $result = new LatteTemplateResolverResult();
        $standaloneTemplateFiles = $this->findStandaloneTemplates($reflectionClass);
        foreach ($standaloneTemplateFiles as $standaloneTemplateFile) {
            $templateContext = $this->getClassGlobalTemplateContext($reflectionClass, $latteContext);
            $result->addTemplate(new Template(
                $standaloneTemplateFile,
                $reflectionClass->getName(),
                null,
                $templateContext
            ));

            $layoutFilePath = $this->layoutPathResolver->resolve($standaloneTemplateFile);
            if ($layoutFilePath !== null) {
                $result->addTemplate(new Template($layoutFilePath, $reflectionClass->getName(), null, $templateContext));
            }
        }
        return $result;
    }

    /**
     * @return string[]
     */
    protected function findStandaloneTemplates(ReflectionClass $reflectionClass): array
    {
        $dir = $this->getClassDir($reflectionClass);
        if ($dir === null) {
            return [];
        }

        $dir = $this->adjustDir($dir);
        $patterns = $this->getTemplatePathPatterns($reflectionClass, $dir);

        $standaloneTemplates = [];
        /** @var SplFileInfo $file */
        foreach (Finder::findFiles('*.latte')->from($dir) as $file) {
            $file = (string)$file;
            if (str_contains($file, '@')) {
                continue;
            }
            foreach ($patterns as $pattern) {
                $matches = [];
                if (preg_match("#$pattern#", $file, $matches)) {
                    if (!$this->isStandaloneTemplate($reflectionClass, $file, $matches)) {
                        continue;
                    }
                    $standaloneTemplates[] = $file;
                }
            }
        }

        return $standaloneTemplates;
    }

    protected function adjustDir(string $dir): string
    {
        return $dir;
    }

    /**
     * @return string[]
     */
    abstract protected function getTemplatePathPatterns(ReflectionClass $reflectionClass, string $dir): array;

    /**
     * @param ReflectionClass $reflectionClass
     * @param string $templateFile
     * @param array<string|string[]> $patternMatches
     * @return bool
     */
    protected function isStandaloneTemplate(ReflectionClass $reflectionClass, string $templateFile, array $patternMatches): bool
    {
        return true;
    }
}
