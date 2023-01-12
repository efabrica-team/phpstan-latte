<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Compiler;

use Composer\InstalledVersions;
use Efabrica\PHPStanLatte\Compiler\Compiler\CompilerInterface;
use Efabrica\PHPStanLatte\Template\Template;
use InvalidArgumentException;
use Latte\Engine;

final class LatteToPhpCompiler
{
    private string $tmpDir;

    private string $cacheKey;

    private CompilerInterface $compiler;

    private Postprocessor $postprocessor;

    public function __construct(
        ?string $tmpDir,
        string $cacheKey,
        CompilerInterface $compiler,
        Postprocessor $postprocessor
    ) {
        $this->tmpDir = $tmpDir ?? sys_get_temp_dir() . '/phpstan-latte';
        $this->cacheKey = $cacheKey . md5(
            Engine::VERSION_ID .
            PHP_VERSION_ID .
            (class_exists(InstalledVersions::class) ? json_encode(InstalledVersions::getAllRawData()) : '')
        );
        $this->compiler = $compiler;
        $this->postprocessor = $postprocessor;
    }

    public function compileFile(Template $template, string $context = ''): string
    {
        $templatePath = $template->getPath();
        if (!file_exists($templatePath)) {
            throw new InvalidArgumentException('Template file "' . $templatePath . '" doesn\'t exist.');
        }
        $templateContent = file_get_contents($templatePath) ?: '';
        $phpContent = $this->compiler->compile($templateContent, $template->getActualClass(), $context);
        $phpContent = $this->postprocessor->postProcess($phpContent, $template);
        $templateDir = pathinfo($templatePath, PATHINFO_DIRNAME);
        $templateFileName = pathinfo($templatePath, PATHINFO_BASENAME);
        $contextHash = md5(
            $templateContent .
            $template->getSignatureHash() .
            $context .
            $this->cacheKey
        );

        $replacedPath = getcwd() ?: '';
        if (strpos($templateDir, $replacedPath) === 0) {
            $templateDir = substr($templateDir, strlen($replacedPath));
        }

        $compileDir = $this->tmpDir . '/' . $templateDir;
        if (!file_exists($compileDir)) {
            mkdir($compileDir, 0777, true);
        }
        $compileFilePath = $compileDir . '/' . $templateFileName . '.' . $contextHash . '.php';
        file_put_contents($compileFilePath, $phpContent);
        return $compileFilePath;
    }
}
