<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\LatteContext\CollectedData;

use Efabrica\PHPStanLatte\Template\Component;
use PhpParser\Node;
use PhpParser\Node\Stmt\ClassMethod;
use PHPStan\Analyser\Scope;
use PHPStan\PhpDoc\TypeStringResolver;
use PHPStan\Type\Type;
use ReturnTypeWillChange;

final class CollectedComponent extends CollectedLatteContextObject
{
    private string $className;

    private string $methodName;

    private Component $component;

    private bool $declared;

    public function __construct(string $className, string $methodName, Component $component, bool $declared = false)
    {
        $this->className = $className;
        $this->methodName = $methodName;
        $this->component = $component;
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

    public function getComponentName(): string
    {
        return $this->component->getName();
    }

    public function getComponentType(): Type
    {
        return $this->component->getType();
    }

    public function getComponentTypeAsString(): string
    {
        return $this->component->getTypeAsString();
    }

    public function getComponent(): Component
    {
        return $this->component;
    }

    public function isDeclared(): bool
    {
        return $this->declared;
    }

    public static function build(?Node $node, Scope $scope, string $name, Type $type, bool $declared = false): self
    {
        $classReflection = $scope->getClassReflection();
        if ($node !== null) {
            $methodName = $node instanceof ClassMethod ? $node->name->name : $scope->getFunctionName();
        } else {
            $methodName = null;
        }
        return new self(
            $classReflection !== null ? $classReflection->getName() : '',
            $methodName ?? '',
            new Component($name, $type),
            $declared
        );
    }

    /**
     * @return array<string, mixed>
     */
    #[ReturnTypeWillChange]
    public function jsonSerialize(): array
    {
        return [
          'class' => self::class,
          'className' => $this->getClassName(),
          'methodName' => $this->getMethodName(),
          'component' => $this->getComponent()->jsonSerialize(),
          'declared' => $this->isDeclared(),
        ];
    }

    /**
     * @param array{class: string, className: string, methodName: string, component: array<string, mixed>, declared: bool} $data
     */
    public static function fromJson(array $data, TypeStringResolver $typeStringResolver): static
    {

        return new self(
            $data['className'],
            $data['methodName'],
            Component::fromJson($data['component'], $typeStringResolver),
            $data['declared']
        );
    }
}
