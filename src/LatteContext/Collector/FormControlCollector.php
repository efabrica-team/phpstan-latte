<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\LatteContext\Collector;

use Efabrica\PHPStanLatte\LatteContext\CollectedData\Form\CollectedFormControl;
use Efabrica\PHPStanLatte\PhpDoc\LattePhpDocResolver;
use Efabrica\PHPStanLatte\Resolver\NameResolver\NameResolver;
use Efabrica\PHPStanLatte\Resolver\ValueResolver\ValueResolver;
use Efabrica\PHPStanLatte\Template\Form\Container;
use Efabrica\PHPStanLatte\Template\Form\Field;
use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\ObjectType;

/**
 * @extends AbstractLatteContextCollector<CollectedFormControl>
 */
final class FormControlCollector extends AbstractLatteContextCollector
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

    public function getNodeTypes(): array
    {
        return [MethodCall::class];
    }

    /**
     * @param MethodCall $node
     * @phpstan-return null|CollectedFormControl[]
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
            $formControlType = $scope->getType($componentArg->value);
            $controlNameArg = $node->getArgs()[1] ?? null;
            $controlNameDefault = null;
        } else {
            // other form methods
            $formClassReflection = $this->reflectionProvider->getClass($formType->getClassName());
            if (!$formClassReflection->hasMethod($formMethodName)) {
                return null;
            }

            $formControlReflectionMethod = $formClassReflection->getMethod($formMethodName, $scope);

            $formControlParametersAcceptor = $formControlReflectionMethod->getVariants()[0] ?? null;
            if ($formControlParametersAcceptor === null) {
                return null;
            }

            $formControlType = $formControlParametersAcceptor->getReturnType();
            $controlNameArg = $node->getArgs()[0] ?? null;

            $formControlParameters = $formControlParametersAcceptor->getParameters();
            $controlNameDefaultType = isset($formControlParameters[0]) ? $formControlParameters[0]->getDefaultValue() : null;
            if ($controlNameDefaultType instanceof ConstantStringType) {
                $controlNameDefault = trim($controlNameDefaultType->getValue(), '"\'');
            } else {
                $controlNameDefault = null;
            }
        }

        if ($controlNameArg !== null) {
            $controlNames = $this->valueResolver->resolveStrings($controlNameArg->value, $scope);
            if ($controlNames === null) {
                return null;
            }
        } elseif ($controlNameDefault !== null) {
            $controlNames = [$controlNameDefault];
        } else {
            return null;
        }

        $formControls = [];
        foreach ($controlNames as $controlName) {
            if ((new ObjectType('Nette\Forms\Container'))->isSuperTypeOf($formControlType)->yes() && !(new ObjectType('Nette\Forms\Form'))->isSuperTypeOf($formControlType)->yes()) {
                $formControl = new Container($controlName, $formControlType);
            } elseif ((new ObjectType('Nette\Forms\Control'))->isSuperTypeOf($formControlType)->yes()) {
                $formControl = new Field($controlName, $formControlType);
            } else {
                continue;
            }

            $formControls[] = new CollectedFormControl(
                $classReflection->getName(),
                $methodName,
                $formControl
            );
        }
        return $formControls;
    }
}
