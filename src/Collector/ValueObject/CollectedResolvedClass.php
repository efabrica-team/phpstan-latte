<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Collector\ValueObject;

use JsonSerializable;
use ReturnTypeWillChange;

final class CollectedResolvedClass implements JsonSerializable
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

    #[ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return [
            'resolver' => $this->resolver,
            'className' => $this->className,
        ];
    }
}
