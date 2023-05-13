<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\LatteContext\CollectedData;

use Efabrica\PHPStanLatte\Template\Variable;
use PhpParser\Node;
use PhpParser\Node\Stmt\ClassMethod;
use PHPStan\Analyser\Scope;
use PHPStan\Type\Type;

final class CollectedVariable extends CollectedLatteContextObject
{
    private string $className;

    private string $methodName;

    private Variable $variable;

    private bool $declared;

    public function __construct(string $className, string $methodName, Variable $variable, bool $declared = false)
    {
        $this->className = $className;
        $this->methodName = $methodName;
        $this->variable = $variable;
        $this->declared = $declared;
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

    public function isDeclared(): bool
    {
        return $this->declared;
    }

    public static function build(Node $node, Scope $scope, string $name, Type $type, bool $declared = false): self
    {
        return new self(
            $scope->getClassReflection() !== null ? $scope->getClassReflection()->getName() : '',
            $node instanceof ClassMethod ? $node->name->name : $scope->getFunctionName() ?? '',
            new Variable($name, $type),
            $declared
        );
    }

    /**
     * @param Variable[] $variables
     * @return CollectedVariable[]
     */
    public static function buildAll(Node $node, Scope $scope, array $variables, bool $declared = false): array
    {
        $collectedVariables = [];
        foreach ($variables as $variable) {
            $collectedVariables[] = self::build($node, $scope, $variable->getName(), $variable->getType(), $declared);
        }
        return $collectedVariables;
    }
}
