<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Compiler;

use Composer\InstalledVersions;
use Efabrica\PHPStanLatte\Compiler\Compiler\CompilerInterface;
use Efabrica\PHPStanLatte\Exception\ParseException;
use Efabrica\PHPStanLatte\Template\Template;
use InvalidArgumentException;
use Latte\CompileException;
use Latte\Engine;
use Nette\Utils\FileSystem;

final class LatteToPhpCompiler
{
    private string $tmpDir;

    private string $cacheKey;

    private CompilerInterface $compiler;

    private Postprocessor $postprocessor;

    private bool $debugMode;

    public function __construct(
        string $cacheKey,
        CompiledTemplateDirResolver $compiledTemplateDirResolver,
        CompilerInterface $compiler,
        Postprocessor $postprocessor,
        bool $debugMode = false
    ) {
        $this->tmpDir = $compiledTemplateDirResolver->resolve();
        if (file_exists($this->tmpDir) && $debugMode) {
            FileSystem::delete($this->tmpDir);
        }
        $this->cacheKey = $cacheKey . md5(
            Engine::VERSION_ID .
            PHP_VERSION_ID .
            $compiler->getCacheKey() .
            $postprocessor->getCacheKey() .
            (class_exists(InstalledVersions::class) ? json_encode(InstalledVersions::getAllRawData()) : '')
        );
        $this->compiler = $compiler;
        $this->postprocessor = $postprocessor;
        $this->debugMode = $debugMode;
    }

    /**
     * @throws CompileException
     * @throws ParseException
     */
    public function compileFile(Template $template, string $context = ''): string
    {
        $templatePath = $template->getPath();
        if (!file_exists($templatePath)) {
            throw new InvalidArgumentException('Template file "' . $templatePath . '" doesn\'t exist.');
        }
        $templateContent = file_get_contents($templatePath) ?: '';
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

        $compileDir = $this->normalizeCompileDir($this->tmpDir . '/' . $templateDir);
        if (!file_exists($compileDir)) {
            mkdir($compileDir, 0777, true);
        }
        $compileFilePath = $compileDir . '/' . $templateFileName . '.' . $contextHash . '.php';

        if (!$this->debugMode && file_exists($compileFilePath)) {
            require($compileFilePath); // load type definitions from compiled template
            return realpath($compileFilePath) ?: '';
        }

        $phpContent = $this->compiler->compile($templateContent, $template->getActualClass(), $context);
        file_put_contents($compileFilePath . '.original', $phpContent);
        return $this->postprocessor->postProcess($phpContent, $template, $compileFilePath);
    }

    private function normalizeCompileDir(string $compileDir): string
    {
        $compileDirParts = array_filter(explode('/', $compileDir));
        $newCompileDirParts = [];
        foreach ($compileDirParts as $compileDirPart) {
            if ($compileDirPart === '..') {
                array_pop($newCompileDirParts);
                continue;
            }
            $newCompileDirParts[] = $compileDirPart;
        }
        return '/' . implode('/', $newCompileDirParts);
    }
}
