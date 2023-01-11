<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\LatteContext\Collector;

use Efabrica\PHPStanLatte\LatteContext\CollectedData\Form\CollectedFormField;
use Efabrica\PHPStanLatte\PhpDoc\LattePhpDocResolver;
use Efabrica\PHPStanLatte\Resolver\NameResolver\NameResolver;
use Efabrica\PHPStanLatte\Resolver\ValueResolver\ValueResolver;
use Efabrica\PHPStanLatte\Template\Form\FormField;
use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\ObjectType;

/**
 * @extends AbstractLatteContextCollector<Node, CollectedFormField>
 */
final class FormFieldCollector extends AbstractLatteContextCollector
{
    private ValueResolver $valueResolver;

    private LattePhpDocResolver $lattePhpDocResolver;

    public function __construct(
        NameResolver $nameResolver,
        ReflectionProvider $reflectionProvider,
        ValueResolver $valueResolver,
        LattePhpDocResolver $lattePhpDocResolver
    ) {
        parent::__construct($nameResolver, $reflectionProvider);
        $this->valueResolver = $valueResolver;
        $this->lattePhpDocResolver = $lattePhpDocResolver;
    }

    public function getNodeType(): string
    {
        return Node::class;
    }

    /**
     * @phpstan-return null|CollectedFormField[]
     */
    public function collectData(Node $node, Scope $scope): ?array
    {
        $classReflection = $scope->getClassReflection();
        if ($classReflection === null) {
            return null;
        }

        $methodName = $scope->getFunctionName();
        if ($methodName === null) {
            return null;
        }

        if (!$node instanceof MethodCall) {
            return null;
        }

        $formType = $scope->getType($node->var);

        if (!$formType instanceof ObjectType) {
            return null;
        }

        if (!$formType->isInstanceOf('Nette\Forms\Container')->yes()) {
            return null;
        }

        if ($this->lattePhpDocResolver->resolveForNode($node, $scope)->isIgnored()) {
            return null;
        }

        /** @var non-empty-string|null $formMethodName */
        $formMethodName = $this->nameResolver->resolve($node->name);
        if ($formMethodName === null) {
            return null;
        }

        if ($formMethodName === 'addComponent') {
            $componentArg = $node->getArgs()[0] ?? null;
            if ($componentArg === null) {
                return null;
            }
            $formFieldType = $scope->getType($componentArg->value);
            $fieldNameArg = $node->getArgs()[1] ?? null;
            $fieldNameDefault = null;
        } else {
            // other form methods
            $formClassReflection = $this->reflectionProvider->getClass($formType->getClassName());
            if (!$formClassReflection->hasMethod($formMethodName)) {
                return null;
            }

            $formFieldReflectionMethod = $formClassReflection->getMethod($formMethodName, $scope);

            $formFieldParametersAcceptor = $formFieldReflectionMethod->getVariants()[0] ?? null;
            if ($formFieldParametersAcceptor === null) {
                return null;
            }

            $formFieldType = $formFieldParametersAcceptor->getReturnType();

            $fieldNameArg = $node->getArgs()[0] ?? null;
            $fieldNameDefaultType = $formFieldParametersAcceptor->getParameters()[0]->getDefaultValue();
            if ($fieldNameDefaultType instanceof ConstantStringType) {
                $fieldNameDefault = trim($fieldNameDefaultType->getValue(), '"\'');
            } else {
                $fieldNameDefault = null;
            }
        }

        if (!$formFieldType instanceof ObjectType) {
            return null;
        }

        if (!$formFieldType->isInstanceOf('Nette\Forms\Container')->yes() &&
            !$formFieldType->isInstanceOf('Nette\Forms\Control')->yes()
        ) {
            return null;
        }

        if ($fieldNameArg !== null) {
            $fieldNames = $this->valueResolver->resolveStrings($fieldNameArg->value, $scope);
            if ($fieldNames === null) {
                return null;
            }
        } elseif ($fieldNameDefault !== null) {
            $fieldNames = [$fieldNameDefault];
        } else {
            return null;
        }

        $formFields = [];
        foreach ($fieldNames as $fieldName) {
            $formFields[] = new CollectedFormField(
                $classReflection->getName(),
                $methodName,
                new FormField($fieldName, $formFieldType)
            );
        }
        return $formFields;
    }
}
