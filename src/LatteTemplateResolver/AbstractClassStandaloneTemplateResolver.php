<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\LatteTemplateResolver;

use Efabrica\PHPStanLatte\LatteContext\LatteContext;
use Efabrica\PHPStanLatte\PhpDoc\LattePhpDocResolver;
use Efabrica\PHPStanLatte\Resolver\LayoutResolver\LayoutPathResolver;
use Efabrica\PHPStanLatte\Template\Template;
use Nette\Utils\Finder;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ReflectionProvider;
use SplFileInfo;
use function preg_match;
use function str_contains;

abstract class AbstractClassStandaloneTemplateResolver extends AbstractClassTemplateResolver
{
    private LayoutPathResolver $layoutPathResolver;

    public function __construct(LattePhpDocResolver $lattePhpDocResolver, ReflectionProvider $reflectionProvider, LayoutPathResolver $layoutPathResolver)
    {
        parent::__construct($lattePhpDocResolver, $reflectionProvider);
        $this->layoutPathResolver = $layoutPathResolver;
    }

    protected function getClassResult(ClassReflection $classReflection, LatteContext $latteContext): LatteTemplateResolverResult
    {
        $result = new LatteTemplateResolverResult();
        $standaloneTemplateFiles = $this->findStandaloneTemplates($classReflection);
        foreach ($standaloneTemplateFiles as $standaloneTemplateFile) {
            $templateContext = $this->getClassGlobalTemplateContext($classReflection, $latteContext);
            $result->addTemplate(new Template(
                $standaloneTemplateFile,
                $classReflection->getName(),
                null,
                $templateContext
            ));

            $layoutFilePath = $this->layoutPathResolver->resolve($standaloneTemplateFile);
            if ($layoutFilePath !== null) {
                $result->addTemplate(new Template($layoutFilePath, $classReflection->getName(), null, $templateContext));
            }
        }
        return $result;
    }

    /**
     * @return string[]
     */
    protected function findStandaloneTemplates(ClassReflection $classReflection): array
    {
        $dir = $this->getClassDir($classReflection);
        if ($dir === null) {
            return [];
        }

        $dir = $this->adjustDir($dir);
        $patterns = $this->getTemplatePathPatterns($classReflection, $dir);

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
                    if (!$this->isStandaloneTemplate($classReflection, $file, $matches)) {
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
    abstract protected function getTemplatePathPatterns(ClassReflection $classReflection, string $dir): array;

    /**
     * @param ClassReflection $classReflection
     * @param string $templateFile
     * @param array<string|string[]> $patternMatches
     * @return bool
     */
    protected function isStandaloneTemplate(ClassReflection $classReflection, string $templateFile, array $patternMatches): bool
    {
        return true;
    }
}
