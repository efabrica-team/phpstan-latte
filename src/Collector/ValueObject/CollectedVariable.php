<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Collector\ValueObject;

use Efabrica\PHPStanLatte\Template\Variable;
use JsonSerializable;
use ReturnTypeWillChange;

final class CollectedVariable implements JsonSerializable
{
    private string $className;

    private string $methodName;

    private Variable $variable;

    public function __construct(string $className, string $methodName, Variable $variable)
    {
        $this->className = $className;
        $this->methodName = $methodName;
        $this->variable = $variable;
    }

    public function getClassName(): string
    {
        return $this->className;
    }

    public function getMethodName(): string
    {
        return $this->methodName;
    }

    public function getVariable(): Variable
    {
        return $this->variable;
    }

    #[ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return [
            'className' => $this->className,
            'methodName' => $this->methodName,
            'variable' => $this->variable,
        ];
    }
}
