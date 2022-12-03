<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Resolver\MethodResolver;

use PHPStan\Type\ObjectType;

final class TerminatingMethodResolver
{
    /**
     * @var array<string, string[]>
     */
    private array $earlyTerminatingMethodCalls;

    /**
     * @param array<string, string[]> $earlyTerminatingMethodCalls
     */
    public function __construct(array $earlyTerminatingMethodCalls)
    {
        $this->earlyTerminatingMethodCalls = $earlyTerminatingMethodCalls;
    }

    public function isTerminatingCall(string $calledClassName, string $calledMethodName): bool
    {
        $objectType = new ObjectType($calledClassName);

        foreach ($this->earlyTerminatingMethodCalls as $class => $methods) {
            foreach ($methods as $method) {
                if ($objectType->isInstanceOf($class)->yes() && $calledMethodName === $method) {
                    return true;
                }
            }
        }

        return false;
    }
}
