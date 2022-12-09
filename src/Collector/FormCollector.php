<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Collector;

use Efabrica\PHPStanLatte\Collector\ValueObject\CollectedForm;
use Efabrica\PHPStanLatte\Collector\ValueObject\CollectedFormField;
use Efabrica\PHPStanLatte\Resolver\NameResolver\NameResolver;
use Efabrica\PHPStanLatte\Resolver\ValueResolver\ValueResolver;
use Efabrica\PHPStanLatte\Type\TypeSerializer;
use PhpParser\Node;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Expression;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\ObjectType;
use PHPStan\Type\VerbosityLevel;

/**
 * @phpstan-import-type CollectedFormArray from CollectedForm
 * @extends AbstractCollector<Node, CollectedForm, CollectedFormArray>
 */
final class FormCollector extends AbstractCollector
{
    private NameResolver $nameResolver;

    private ValueResolver $valueResolver;

    private ReflectionProvider $reflectionProvider;

    public function __construct(TypeSerializer $typeSerializer, NameResolver $nameResolver, ValueResolver $valueResolver, ReflectionProvider $reflectionProvider)
    {
        parent::__construct($typeSerializer);
        $this->nameResolver = $nameResolver;
        $this->valueResolver = $valueResolver;
        $this->reflectionProvider = $reflectionProvider;
    }

    public function getNodeType(): string
    {
        return Node::class;
    }

    /**
     * @phpstan-return null|CollectedFormArray[]
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
     * @phpstan-return null|CollectedFormArray[]
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

        $formClassReflection = $this->reflectionProvider->getClass($returnType->describe(VerbosityLevel::typeOnly()));
        $formFields = [];
        foreach ($node->stmts ?: [] as $stmt) {
            if (!$stmt instanceof Expression) {
                continue;
            }

            $methodCall = $this->findMethodCallForExpression($stmt);
            if ($methodCall === null) {
                continue;
            }

            /** @var non-empty-string|null $formMethodName */
            $formMethodName = $this->nameResolver->resolve($methodCall->name);
            if ($formMethodName === null) {
                continue;
            }

            if (!$formClassReflection->hasMethod($formMethodName)) {
                continue;
            }

            $formFieldReflectionMethod = $formClassReflection->getMethod($formMethodName, $scope);

            $formFieldParametersAcceptor = $formFieldReflectionMethod->getVariants()[0] ?? null;
            if ($formFieldParametersAcceptor === null) {
                continue;
            }

            $formFieldMethodReturnType = $formFieldParametersAcceptor->getReturnType();
            if (!$formFieldMethodReturnType instanceof ObjectType) {
                continue;
            }

            if (!$formFieldMethodReturnType->isInstanceOf('Nette\Forms\Container')->yes() && !$formFieldMethodReturnType->isInstanceOf('Nette\Forms\Controls\BaseControl')->yes()) {
                continue;
            }

            $fieldNameArg = $methodCall->getArgs()[0] ?? null;
            if ($fieldNameArg === null) {
                continue;
            }

            $fieldNames = $this->valueResolver->resolve($fieldNameArg->value, $scope);
            if ($fieldNames === null) {
                continue;
            }

            foreach ($fieldNames as $fieldName) {
                if (!is_string($fieldName)) {
                    continue;
                }
                $formFields[] = new CollectedFormField($fieldName, $formFieldMethodReturnType);
            }
        }

        $formName = lcfirst(str_replace('createComponent', '', $methodName));
        return $this->collectItem(new CollectedForm(
            $classReflection->getName(),
            '',
            $formName,
            $returnType,
            $formFields
        ));
    }

    private function findMethodCallForExpression(Expression $expression): ?MethodCall
    {
        $methodCall = null;
        if ($expression->expr instanceof MethodCall) {
            $methodCall = $this->findMethodCall($expression->expr);
        } elseif ($expression->expr instanceof Assign && $expression->expr->expr instanceof MethodCall) {
            $methodCall = $expression->expr->expr;
        }
        return $methodCall;
    }

    private function findMethodCall(MethodCall $methodCall): ?MethodCall
    {
        if ($methodCall->var instanceof MethodCall) {
            return $this->findMethodCall($methodCall->var);
        }
        return $methodCall;
    }
}
