<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Collector\ValueObject;

use Efabrica\PHPStanLatte\Template\Variable;
use Efabrica\PHPStanLatte\Type\TypeSerializer;
use PhpParser\Node;
use PhpParser\Node\Stmt\ClassMethod;
use PHPStan\Analyser\Scope;
use PHPStan\Type\Type;

/**
 * @phpstan-type CollectedVariableArray array{className: string, methodName: string, variableName: string, variableType: array<string, string>}
 */
final class CollectedVariable extends CollectedValueObject
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
    public function toArray(TypeSerializer $typeSerializer): array
    {
        return [
            'className' => $this->className,
            'methodName' => $this->methodName,
            'variableName' => $this->getVariableName(),
            'variableType' => $typeSerializer->toArray($this->getVariableType()),
        ];
    }

    /**
     * @phpstan-param CollectedVariableArray $item
     */
    public static function fromArray(array $item, TypeSerializer $typeSerializer): self
    {
        $variable = new Variable($item['variableName'], $typeSerializer->fromArray($item['variableType']));
        return new CollectedVariable($item['className'], $item['methodName'], $variable);
    }

    public static function build(Node $node, Scope $scope, string $name, Type $type): self
    {
        $classReflection = $scope->getTraitReflection() ?: $scope->getClassReflection();
        return new self(
            $classReflection !== null ? $classReflection->getName() : '',
            $node instanceof ClassMethod ? $node->name->name : $scope->getFunctionName() ?? '',
            new Variable($name, $type)
        );
    }
}
