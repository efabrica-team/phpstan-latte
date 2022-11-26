<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Collector\ValueObject;

/**
 * @phpstan-type CollectedResolvedClassArray array{resolver: string, className: string}
 */
final class CollectedResolvedClass
{
    private string $resolver;

    private string $className;

    public function __construct(string $resolver, string $className)
    {
        $this->resolver = $resolver;
        $this->className = $className;
    }

    public function getResolver(): string
    {
        return $this->resolver;
    }

    public function getClassName(): string
    {
        return $this->className;
    }

    /**
     * @phpstan-return CollectedResolvedClassArray
     */
    public function toArray()
    {
        return [
            'resolver' => $this->resolver,
            'className' => $this->className,
        ];
    }
}
