<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Collector;

use Efabrica\PHPStanLatte\Collector\ValueObject\CollectedForm;
use Efabrica\PHPStanLatte\Collector\ValueObject\CollectedFormField;
use Efabrica\PHPStanLatte\Resolver\NameResolver\NameResolver;
use Efabrica\PHPStanLatte\Resolver\ValueResolver\ValueResolver;
use PhpParser\Node;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Expression;
use PHPStan\Analyser\Scope;
use PHPStan\BetterReflection\BetterReflection;
use PHPStan\BetterReflection\Reflection\ReflectionNamedType;
use PHPStan\Collectors\Collector;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Type\ObjectType;
use PHPStan\Type\VerbosityLevel;

/**
 * @phpstan-import-type CollectedFormArray from CollectedForm
 * @implements Collector<Node, ?CollectedFormArray>
 */
final class FormCollector implements Collector
{
    private NameResolver $nameResolver;

    private ValueResolver $valueResolver;

    public function __construct(NameResolver $nameResolver, ValueResolver $valueResolver)
    {
        $this->nameResolver = $nameResolver;
        $this->valueResolver = $valueResolver;
    }

    public function getNodeType(): string
    {
        return Node::class;
    }

    /**
     * @phpstan-return null|CollectedFormArray
     */
    public function processNode(Node $node, Scope $scope): ?array
    {
        $classReflection = $scope->getClassReflection();
        if ($classReflection === null) {
            return null;
        }

        if ($node instanceof ClassMethod) {
            return $this->findCreateComponent($node, $classReflection, $scope);
        }

        return null;
    }

    /**
     * @phpstan-return null|CollectedFormArray
     */
    private function findCreateComponent(ClassMethod $node, ClassReflection $classReflection, Scope $scope): ?array
    {
        // TODO check if actual class is control / presenter

        $methodName = $this->nameResolver->resolve($node->name);
        if ($methodName === null || !str_starts_with($methodName, 'createComponent') || $methodName === 'createComponent') {
            return null;
        }

        $methodReflection = $classReflection->getNativeMethod($methodName);
        $parametersAcceptor = $methodReflection->getVariants()[0] ?? null;
        if ($parametersAcceptor === null) {
            return null;
        }

        $returnType = $parametersAcceptor->getReturnType();
        if (!$returnType instanceof ObjectType) {
            return null;
        }

        if (!$returnType->isInstanceOf('Nette\Forms\Form')->yes()) {
            return null;
        }

        // TODO find real form class and use it (e.g. $form = new Form())
        $formClassReflection = (new BetterReflection())->reflector()->reflectClass('Nette\Forms\Form');

        $formFields = [];
        foreach ($node->stmts ?: [] as $stmt) {
            if (!$stmt instanceof Expression) {
                continue;
            }

            $methodCall = $this->findMethodCall($stmt);
            if ($methodCall === null) {
                continue;
            }

            /** @var non-empty-string|null $formMethodName */
            $formMethodName = $this->nameResolver->resolve($methodCall->name);
            if ($formMethodName === null) {
                continue;
            }

            $formFieldReflectionMethod = $formClassReflection->getMethod($formMethodName);
            if ($formFieldReflectionMethod === null) {
                continue;
            }

            $formFieldMethodReturnTypeReflection = $formFieldReflectionMethod->getReturnType();
            if (!$formFieldMethodReturnTypeReflection instanceof ReflectionNamedType) {
                continue;
            }

            $formFieldMethodReturnType = new ObjectType($formFieldMethodReturnTypeReflection->getName());
            if (!$formFieldMethodReturnType->isInstanceOf('Nette\Forms\Container')->yes() && !$formFieldMethodReturnType->isInstanceOf('Nette\Forms\Controls\BaseControl')->yes()) {
                continue;
            }

            $fieldNameArg = $methodCall->getArgs()[0] ?? null;
            if ($fieldNameArg === null) {
                continue;
            }

            $fieldName = $this->valueResolver->resolve($fieldNameArg->value);
            if (!is_string($fieldName)) {
                continue;
            }

            $formFields[] = new CollectedFormField($fieldName, $formFieldMethodReturnType->describe(VerbosityLevel::typeOnly()));
        }

        $formName = lcfirst(str_replace('createComponent', '', $methodName));

        return (new CollectedForm(
            $classReflection->getName(),
            '',
            $formName,
            $formFields
        ))->toArray();
    }

    private function findMethodCall(Expression $expression): ?MethodCall
    {
        $methodCall = null;
        if ($expression->expr instanceof MethodCall) {
            if ($expression->expr->var instanceof MethodCall) {
                $methodCall = $expression->expr->var;
            } else {
                $methodCall = $expression->expr;
            }
        } elseif ($expression->expr instanceof Assign && $expression->expr->expr instanceof MethodCall) {
            $methodCall = $expression->expr->expr;
        }
        return $methodCall;
    }
}
