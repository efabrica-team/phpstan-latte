<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\LatteTemplateResolver;

use Efabrica\PHPStanLatte\LatteContext\LatteContext;
use Efabrica\PHPStanLatte\Template\Template;
use Nette\Utils\Finder;
use PHPStan\BetterReflection\Reflection\ReflectionClass;

abstract class AbstractClassStandaloneTemplateResolver extends AbstractClassTemplateResolver
{
    protected function getClassResult(ReflectionClass $reflectionClass, LatteContext $latteContext): LatteTemplateResolverResult
    {
        $result = new LatteTemplateResolverResult();
        $standaloneTemplateFiles = $this->findStandaloneTemplates($reflectionClass);
        foreach ($standaloneTemplateFiles as $standaloneTemplateFile) {
            $result->addTemplate(new Template(
                $standaloneTemplateFile,
                $reflectionClass->getName(),
                null,
                $this->getClassGlobalTemplateContext($reflectionClass, $latteContext)
            ));
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

        $patterns = $this->getTemplatePathPatterns($reflectionClass, $dir);

        $standaloneTemplates = [];
        /** @var string $file */
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
