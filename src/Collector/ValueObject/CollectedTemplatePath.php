<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Collector\ValueObject;

use Efabrica\PHPStanLatte\Type\TypeSerializer;

/**
 * @phpstan-type CollectedTemplatePathArray array{className: string, methodName: string, templatePath: ?string}
 */
final class CollectedTemplatePath extends CollectedValueObject
{
    private string $className;

    private string $methodName;

    private ?string $templatePath;

    public function __construct(string $className, string $methodName, ?string $templatePath)
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

    public function getTemplatePath(): ?string
    {
        return $this->templatePath;
    }

    /**
     * @phpstan-return CollectedTemplatePathArray
     */
    public function toArray(TypeSerializer $typeSerializer): array
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
    public static function fromArray(array $item, TypeSerializer $typeSerializer): self
    {
        return new CollectedTemplatePath($item['className'], $item['methodName'], $item['templatePath']);
    }
}
