<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Collector\ValueObject;

use Efabrica\PHPStanLatte\Type\TypeSerializer;

/**
 * @phpstan-type CollectedMethodArray array{className: string, methodName: string, alwaysTerminated: bool}
 */
final class CollectedMethod extends CollectedValueObject
{
    private string $className;

    private string $methodName;

    private bool $alwaysTerminated;

    public function __construct(string $className, string $methodName, bool $alwaysTerminated)
    {
        $this->className = $className;
        $this->methodName = $methodName;
        $this->alwaysTerminated = $alwaysTerminated;
    }

    public function getClassName(): string
    {
        return $this->className;
    }

    public function getMethodName(): string
    {
        return $this->methodName;
    }

    public function isAlwaysTerminated(): bool
    {
        return $this->alwaysTerminated;
    }

    /**
     * @phpstan-return CollectedMethodArray
     */
    public function toArray(TypeSerializer $typeSerializer): array
    {
        return [
            'className' => $this->className,
            'methodName' => $this->methodName,
            'alwaysTerminated' => $this->alwaysTerminated,
        ];
    }

    /**
     * @phpstan-param CollectedMethodArray $item
     */
    public static function fromArray(array $item, TypeSerializer $typeSerializer): self
    {
        return new CollectedMethod($item['className'], $item['methodName'], $item['alwaysTerminated']);
    }

    public static function unknown(string $className, string $methodName): self
    {
        return new CollectedMethod($className, $methodName, false);
    }
}
