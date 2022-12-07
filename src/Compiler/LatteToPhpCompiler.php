<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Compiler;

use Efabrica\PHPStanLatte\Collector\ValueObject\CollectedForm;
use Efabrica\PHPStanLatte\Compiler\Compiler\CompilerInterface;
use Efabrica\PHPStanLatte\Template\Component;
use Efabrica\PHPStanLatte\Template\Variable;
use InvalidArgumentException;

final class LatteToPhpCompiler
{
    private string $tmpDir;

    private CompilerInterface $compiler;

    private Postprocessor $postprocessor;

    public function __construct(
        ?string $tmpDir,
        CompilerInterface $compiler,
        Postprocessor $postprocessor
    ) {
        $this->tmpDir = $tmpDir ?? sys_get_temp_dir() . '/phpstan-latte';
        $this->compiler = $compiler;
        $this->postprocessor = $postprocessor;
    }

    /**
     * @param Variable[] $variables
     * @param Component[] $components
     * @param CollectedForm[] $forms
     */
    public function compileFile(?string $actualClass, string $templatePath, array $variables, array $components, array $forms, string $context = ''): string
    {
        if (!file_exists($templatePath)) {
            throw new InvalidArgumentException('Template file "' . $templatePath . '" doesn\'t exist.');
        }
        $templateContent = file_get_contents($templatePath) ?: '';
        $phpContent = $this->compiler->compile($templateContent, $actualClass, $context);
        $phpContent = $this->postprocessor->postProcess($actualClass, $phpContent, $variables, $components, $forms);
        $templateDir = pathinfo($templatePath, PATHINFO_DIRNAME);
        $templateFileName = pathinfo($templatePath, PATHINFO_BASENAME);
        $contextHash = md5(
            $actualClass .
            json_encode($variables) .
            json_encode($components) .
            $context
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
