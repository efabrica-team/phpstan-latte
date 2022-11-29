<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Collector\ValueObject;

/**
 * @phpstan-type CollectedTemplatePathArray array{className: string, methodName: string, templatePath: string}
 */
final class CollectedTemplatePath
{
    private string $className;

    private string $methodName;

    private string $templatePath;

    public function __construct(string $className, string $methodName, string $templatePath)
    {
        $this->className = $className;
        $this->methodName = $methodName;
        $this->templatePath = $templatePath;
    }

    public function getClassName(): string
    {
        return $this->className;
    }

    public function getMethodName(): string
    {
        return $this->methodName;
    }

    public function getTemplatePath(): string
    {
        return $this->templatePath;
    }

    /**
     * @phpstan-return CollectedTemplatePathArray
     */
    public function toArray(): array
    {
        return [
            'className' => $this->className,
            'methodName' => $this->methodName,
            'templatePath' => $this->templatePath,
        ];
    }

    /**
     * @phpstan-param CollectedTemplatePathArray $item
     */
    public static function fromArray(array $item): self
    {
        return new CollectedTemplatePath($item['className'], $item['methodName'], $item['templatePath']);
    }
}
