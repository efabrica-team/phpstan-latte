<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\LatteContext\CollectedData;

use Efabrica\PHPStanLatte\Template\Component;
use Efabrica\PHPStanLatte\Template\Variable;
use PhpParser\Node;
use PhpParser\Node\Stmt\ClassMethod;
use PHPStan\Analyser\Scope;

final class CollectedTemplateRender extends CollectedLatteContextObject
{
    /** @var null|string|false */
    private $templatePath;

    /** @var Variable[] */
    private array $variables;

    /** @var Component[] */
    private array $components;

    private string $className;

    private string $methodName;

    private string $file;

    private int $line;

    /**
     * @param null|string|false $templatePath (false = resolve error)
     * @param Variable[] $variables
     * @param Component[] $components
     */
    public function __construct($templatePath, array $variables, array $components, string $className, string $methodName, string $file, int $line)
    {
        $this->templatePath = $templatePath;
        $this->variables = $variables;
        $this->components = $components;
        $this->className = $className;
        $this->methodName = $methodName;
        $this->file = $file;
        $this->line = $line;
    }

    /**
     * @return false|string|null (false = resolve error)
     */
    public function getTemplatePath()
    {
        return $this->templatePath;
    }

    /**
     * @return Variable[]
     */
    public function getVariables(): array
    {
        return $this->variables;
    }

    /**
     * @return Component[]
     */
    public function getComponents(): array
    {
        return $this->components;
    }

    public function getClassName(): string
    {
        return $this->className;
    }

    public function getMethodName(): string
    {
        return $this->methodName;
    }

    public function getFile(): string
    {
        return $this->file;
    }

    public function getLine(): int
    {
        return $this->line;
    }

    public function withError(): self
    {
        return new CollectedTemplateRender(false, $this->variables, $this->components, $this->className, $this->methodName, $this->file, $this->line);
    }

    public function withTemplatePath(?string $templatePath): self
    {
        return new CollectedTemplateRender($templatePath, $this->variables, $this->components, $this->className, $this->methodName, $this->file, $this->line);
    }

    /**
     * @param false|string|null $path
     * @param Variable[] $variables
     * @param Component[] $components
     */
    public static function build(Node $node, Scope $scope, $path, array $variables = [], array $components = []): self
    {
        return new self(
            $path,
            $variables,
            $components,
            $scope->getClassReflection() !== null ? $scope->getClassReflection()->getName() : '',
            $node instanceof ClassMethod ? $node->name->name : $scope->getFunctionName() ?? '',
            $scope->getFile(),
            $node->getStartLine()
        );
    }
}
