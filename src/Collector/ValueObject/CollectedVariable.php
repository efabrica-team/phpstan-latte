<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Collector\ValueObject;

use Efabrica\PHPStanLatte\Template\Variable;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\Type;

/**
 * @phpstan-type CollectedVariableArray array{className: string, methodName: string, variableName: string, variableType: string}
 */
final class CollectedVariable
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

    public function getVariableName(): string
    {
        return $this->variable->getName();
    }

    public function getVariableType(): Type
    {
        return $this->variable->getType();
    }

    public function getVariableTypeAsString(): string
    {
        return $this->variable->getTypeAsString();
    }

    public function getVariable(): Variable
    {
        return $this->variable;
    }

    /**
     * @phpstan-return CollectedVariableArray
     */
    public function toArray(): array
    {
        return [
            'className' => $this->className,
            'methodName' => $this->methodName,
            'variableName' => $this->getVariableName(),
            'variableType' => serialize($this->getVariableType()),
        ];
    }

    /**
     * @phpstan-param CollectedVariableArray $item
     */
    public static function fromArray(array $item): self
    {
        $type = unserialize($item['variableType']);
        if (!$type instanceof Type) {
            throw new ShouldNotHappenException('Cannot unserialize variable type');
        }
        $variable = new Variable($item['variableName'], $type);
        return new CollectedVariable($item['className'], $item['methodName'], $variable);
    }
}
