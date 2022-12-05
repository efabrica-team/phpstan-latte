<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Collector\ValueObject;

/**
 * @phpstan-type CollectedMethodCallArray array{callerClassName: string, callerMethodName: string, calledClassName: string, calledMethodName: string, type: string}
 */
final class CollectedMethodCall
{
    public const CALL = 'call';
    public const TERMINATING_CALL = 'terminating';

    private string $callerClassName;

    private string $callerMethodName;

    private string $calledClassName;

    private string $calledMethodName;

    private string $type;

    public function __construct(string $callerClassName, string $callerMethodName, string $calledClassName, string $calledMethodName, string $type = self::CALL)
    {
        $this->callerClassName = $callerClassName;
        $this->callerMethodName = $callerMethodName;
        $this->calledClassName = $calledClassName;
        $this->calledMethodName = $calledMethodName;
        $this->type = $type;
    }

    public function getCallerClassName(): string
    {
        return $this->callerClassName;
    }

    public function getCallerMethodName(): string
    {
        return $this->callerMethodName;
    }

    public function getCalledClassName(): string
    {
        return $this->calledClassName;
    }

    public function getCalledMethodName(): string
    {
        return $this->calledMethodName;
    }

    public function isCall(): bool
    {
        return $this->type === self::CALL;
    }

    public function isTerminatingCall(): bool
    {
        return $this->type === self::TERMINATING_CALL;
    }

    /**
     * @phpstan-return CollectedMethodCallArray
     */
    public function toArray(): array
    {
        return [
            'callerClassName' => $this->callerClassName,
            'callerMethodName' => $this->callerMethodName,
            'calledClassName' => $this->calledClassName,
            'calledMethodName' => $this->calledMethodName,
            'type' => $this->type,
        ];
    }

    /**
     * @phpstan-param CollectedMethodCallArray $item
     */
    public static function fromArray(array $item): self
    {
        return new CollectedMethodCall($item['callerClassName'], $item['callerMethodName'], $item['calledClassName'], $item['calledMethodName'], $item['type']);
    }
}
