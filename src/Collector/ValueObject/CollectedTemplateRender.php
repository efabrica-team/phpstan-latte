<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Collector\ValueObject;

use Efabrica\PHPStanLatte\Template\Variable;
use Efabrica\PHPStanLatte\Type\TypeSerializer;
use PHPStan\ShouldNotHappenException;

/**
 * @phpstan-type CollectedTemplateRenderArray array{templatePath: string|bool|null, variables: array<array{name: string, type: array<string, string>}>, className: string, methodName: string, file: string, line: int}
 */
final class CollectedTemplateRender extends CollectedValueObject
{
    /** @var null|string|false */
    private $templatePath;

    /** @var Variable[] */
    private array $variables;

    private string $className;

    private string $methodName;

    private string $file;

    private int $line;

    /**
     * @param null|string|false $templatePath (false = resolve error)
     * @param Variable[] $variables
     */
    public function __construct($templatePath, array $variables, string $className, string $methodName, string $file, int $line)
    {
        $this->templatePath = $templatePath;
        $this->variables = $variables;
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

    /**
     * @phpstan-return CollectedTemplateRenderArray
     */
    public function toArray(TypeSerializer $typeSerializer): array
    {
        $variables = [];
        foreach ($this->variables as $variable) {
            $variables[] = [
                'name' => $variable->getName(),
                'type' => $typeSerializer->toArray($variable->getType()),
            ];
        }
        return [
            'templatePath' => $this->templatePath,
            'variables' => $variables,
            'className' => $this->className,
            'methodName' => $this->methodName,
            'file' => $this->file,
            'line' => $this->line,
        ];
    }

    /**
     * @phpstan-param CollectedTemplateRenderArray $item
     */
    public static function fromArray(array $item, TypeSerializer $typeSerializer): self
    {
        $variables = [];
        foreach ($item['variables'] as $variable) {
            $variables[] = new Variable($variable['name'], $typeSerializer->fromArray($variable['type']));
        }
        if ($item['templatePath'] === true) {
            throw new ShouldNotHappenException('TemplatePath cannot be true, only string, null or false allowed.');
        }
        return new CollectedTemplateRender($item['templatePath'], $variables, $item['className'], $item['methodName'], $item['file'], $item['line']);
    }

    public function withError(): self
    {
        return new CollectedTemplateRender(false, $this->variables, $this->className, $this->methodName, $this->file, $this->line);
    }

    public function withTemplatePath(?string $templatePath): self
    {
        return new CollectedTemplateRender($templatePath, $this->variables, $this->className, $this->methodName, $this->file, $this->line);
    }
}
