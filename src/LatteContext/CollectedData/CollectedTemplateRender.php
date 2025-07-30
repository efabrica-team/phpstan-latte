<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\LatteContext\CollectedData;

use Efabrica\PHPStanLatte\Template\Component;
use Efabrica\PHPStanLatte\Template\Variable;
use PhpParser\Node;
use PhpParser\Node\Stmt\ClassMethod;
use PHPStan\Analyser\Scope;
use PHPStan\PhpDoc\TypeStringResolver;

final class CollectedTemplateRender extends CollectedLatteContextObject
{
    private ?string $templatePath;

    /** @var Variable[] */
    private array $variables;

    /** @var Component[] */
    private array $components;

    private string $className;

    private string $methodName;

    private string $file;

    private int $line;

    /**
     * @param Variable[] $variables
     * @param Component[] $components
     */
    public function __construct(?string $templatePath, array $variables, array $components, string $className, string $methodName, string $file, int $line)
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
     * @return ?string
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

    public function withTemplatePath(?string $templatePath): self
    {
        return new CollectedTemplateRender($templatePath, $this->variables, $this->components, $this->className, $this->methodName, $this->file, $this->line);
    }

    /**
     * @param ?string $path
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

    /**
     * @param array<?string> $paths
     * @param Variable[] $variables
     * @param Component[] $components
     * @return array<CollectedTemplateRender|CollectedError>
     */
    public static function buildAll(Node $node, Scope $scope, array $paths, array $variables, array $components = []): array
    {
        $templateRenders = [];
        foreach ($paths as $path) {
            $templateRenders[] = CollectedTemplateRender::build($node, $scope, $path, $variables, $components);
        }
        return $templateRenders;
    }

    public function jsonSerialize(): array
    {
        return [
            'templatePath' => $this->templatePath,
            'variables' => array_map(fn(Variable $variable) => $variable->jsonSerialize(), $this->variables),
            'components' => array_map(fn(Component $component) => $component->jsonSerialize(), $this->components),
            'className' => $this->className,
            'methodName' => $this->methodName,
            'file' => $this->file,
            'line' => $this->line,
        ];
    }

    public static function fromJson(array $data, TypeStringResolver $typeStringResolver): self
    {
        return new self(
            $data['templatePath'] ?? null,
            array_map(fn(array $variable) => Variable::fromJson($variable, $typeStringResolver), $data['variables'] ?? []),
            array_map(fn(array $component) => Component::fromJson($component, $typeStringResolver), $data['components'] ?? []),
            $data['className'] ?? '',
            $data['methodName'] ?? '',
            $data['file'] ?? '',
            $data['line'] ?? 0
        );
    }
}
