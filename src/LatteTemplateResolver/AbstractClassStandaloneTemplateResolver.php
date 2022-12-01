<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\LatteTemplateResolver;

use Efabrica\PHPStanLatte\Template\Template;
use PHPStan\BetterReflection\Reflection\ReflectionClass;
use PHPStan\Node\CollectedDataNode;
use Symfony\Component\Finder\Finder;

abstract class AbstractClassStandaloneTemplateResolver extends AbstractClassTemplateResolver
{
    protected function getClassTemplates(ReflectionClass $reflectionClass, CollectedDataNode $collectedDataNode): array
    {
        $templates = [];
        $standaloneTemplateFiles = $this->findStandaloneTemplates($reflectionClass);
        foreach ($standaloneTemplateFiles as $standaloneTemplateFile) {
            $templates[] = new Template(
                $standaloneTemplateFile,
                $reflectionClass->getName(),
                null,
                $this->getClassGlobalVariables($reflectionClass),
                $this->getClassGlobalComponents($reflectionClass)
            );
        }
        return $templates;
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
        foreach (Finder::create()->in($dir)->name('*.latte') as $file) {
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
