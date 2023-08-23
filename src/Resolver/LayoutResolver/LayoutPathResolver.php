<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Resolver\LayoutResolver;

final class LayoutPathResolver
{
    public function resolve(?string $templatePath): ?string
    {
        if ($templatePath === null) {
            return null;
        }

        $templateContent = file_get_contents($templatePath) ?: '';
        preg_match('/{(layout|extend) (?<layout_name>.*?)}/', $templateContent, $match);

        if (isset($match['layout_name'])) {
            $layoutFilePath = dirname($templatePath) . '/' . $match['layout_name'];
            return $layoutFilePath;
        }

        $layoutFilePath = realpath(dirname($templatePath) . '/@layout.latte') ?: null;
        if ($layoutFilePath !== null) {
            return $layoutFilePath;
        }

        $layoutFilePath = realpath(dirname($templatePath) . '/../@layout.latte') ?: null;
        return $layoutFilePath;
    }
}
