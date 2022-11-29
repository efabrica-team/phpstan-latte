<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Collector\ValueObject;

use Efabrica\PHPStanLatte\Template\Variable;
use PHPStan\PhpDoc\TypeStringResolver;

/**
 * @phpstan-type CollectedIncludePathArray array{path: string, variables: array<array{variableName: string, variableType: string}>}
 */
final class CollectedIncludePath
{
    private string $path;

    /** @var Variable[] */
    private array $variables;

    /**
     * @param Variable[] $variables
     */
    public function __construct(string $path, array $variables)
    {
        $this->path = $path;
        $this->variables = $variables;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * @return Variable[]
     */
    public function getVariables(): array
    {
        return $this->variables;
    }

    /**
     * @phpstan-return CollectedIncludePathArray
     */
    public function toArray(): array
    {
        $variables = [];
        foreach ($this->variables as $variable) {
            $variables[] = [
                'variableName' => $variable->getName(),
                'variableType' => $variable->getTypeAsString(),
            ];
        }
        return [
            'path' => $this->path,
            'variables' => $variables,
        ];
    }

    /**
     * @phpstan-param CollectedIncludePathArray $item
     */
    public static function fromArray(array $item, TypeStringResolver $typeStringResolver): self
    {
        $variables = [];
        foreach ($item['variables'] as $variable) {
            $variables[] = Variable::fromArray($variable, $typeStringResolver);
        }
        return new CollectedIncludePath($item['path'], $variables);
    }
}
