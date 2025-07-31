<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Resolver\LayoutResolver;

final class LayoutPathResolver
{
    private bool $featureAnalyseLayoutFiles;

    public function __construct(bool $featureAnalyseLayoutFiles)
    {
        $this->featureAnalyseLayoutFiles = $featureAnalyseLayoutFiles;
    }

    public function resolve(?string $templatePath): ?string
    {
        if (!$this->featureAnalyseLayoutFiles) {
            return null;
        }

        if ($templatePath === null) {
            return null;
        }

        $templateContent = file_get_contents($templatePath) ?: '';
        preg_match('/{(layout|extend) (?<layout_name>.*?)}/', $templateContent, $match);

        if (isset($match['layout_name'])) {
            $layoutFilePath = dirname($templatePath) . DIRECTORY_SEPARATOR . $match['layout_name'];
            return $layoutFilePath;
        }

        $layoutFilePath = realpath(dirname($templatePath) . DIRECTORY_SEPARATOR . '@layout.latte') ?: null;
        if ($layoutFilePath !== null) {
            return $layoutFilePath;
        }

        $layoutFilePath = realpath(dirname($templatePath) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '@layout.latte') ?: null;
        return $layoutFilePath;
    }
}
