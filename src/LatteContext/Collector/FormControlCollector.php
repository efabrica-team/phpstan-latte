<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\LatteContext\Collector;

use Efabrica\PHPStanLatte\LatteContext\CollectedData\Form\CollectedFormControl;
use Efabrica\PHPStanLatte\PhpDoc\LattePhpDocResolver;
use Efabrica\PHPStanLatte\Resolver\NameResolver\FormControlNameResolver;
use Efabrica\PHPStanLatte\Resolver\NameResolver\NameResolver;
use Efabrica\PHPStanLatte\Template\Form\Container;
use Efabrica\PHPStanLatte\Template\Form\Field;
use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;

/**
 * @extends AbstractLatteContextCollector<CollectedFormControl>
 */
final class FormControlCollector extends AbstractLatteContextCollector
{
    private FormControlNameResolver $formControlNameResolver;

    private LattePhpDocResolver $lattePhpDocResolver;

    public function __construct(
        NameResolver $nameResolver,
        ReflectionProvider $reflectionProvider,
        FormControlNameResolver $formControlNameResolver,
        LattePhpDocResolver $lattePhpDocResolver
    ) {
        parent::__construct($nameResolver, $reflectionProvider);
        $this->formControlNameResolver = $formControlNameResolver;
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
        if (!(new ObjectType('Nette\Forms\Container'))->isSuperTypeOf($formType)->yes()) {
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

        // todo lowercase method names - do this everywhere
        // todo add parameter for developers to extend this array with they own methods
        if (in_array($formMethodName, ['setTranslator', 'setRenderer', 'setDefaults'], true)) {
            return null;
        }
        
        $controlOptions = null;
        if ($formMethodName === 'addComponent') {
            $componentArg = $node->getArgs()[0] ?? null;
            if ($componentArg === null) {
                return null;
            }
            $formControlType = $scope->getType($componentArg->value);
            $controlNameArg = $node->getArgs()[1] ?? null;
            $controlNameDefault = null;
        } else {
            $objectClassNames = $formType->getObjectClassNames();
            if ($objectClassNames === []) {
                return null;
            }
            // other form methods
            $formClassReflection = $this->reflectionProvider->getClass($objectClassNames[0]);
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

            $controlOptions = $this->getControlOptions($node, $formControlType, $scope);

            $formControlParameters = $formControlParametersAcceptor->getParameters();
            $controlNameDefaultType = isset($formControlParameters[0]) ? $formControlParameters[0]->getDefaultValue() : null;
            $constantStringTypes = $controlNameDefaultType !== null ? $controlNameDefaultType->getConstantStrings() : [];
            if ($constantStringTypes !== []) {
                $controlNameDefault = trim($constantStringTypes[0]->getValue(), '"\'');
            } else {
                $controlNameDefault = null;
            }
        }

        if ($controlNameArg !== null) {
            $controlNames = $this->formControlNameResolver->resolve($controlNameArg->value, $scope);
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
            $controlName = (string)$controlName;
            if ((new ObjectType('Nette\Forms\Container'))->isSuperTypeOf($formControlType)->yes() && !(new ObjectType('Nette\Forms\Form'))->isSuperTypeOf($formControlType)->yes()) {
                $formControl = new Container($controlName, $formControlType);
            } elseif ((new ObjectType('Nette\Forms\Control'))->isSuperTypeOf($formControlType)->yes()) {
                $formControl = new Field($controlName, $formControlType, $controlOptions);
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

    /**
     * @return ?array<int|string, int|string> - we don't care about values, so we use only keys as keys and also as values here
     */
    private function getControlOptions(MethodCall $node, Type $formControlType, Scope $scope): ?array
    {
        if (!((new ObjectType('Nette\Forms\Controls\CheckboxList'))->isSuperTypeOf($formControlType)->yes() || (new ObjectType('Nette\Forms\Controls\RadioList'))->isSuperTypeOf($formControlType)->yes())) {
            return null;
        }

        $controlOptionsArg = $node->getArgs()[2] ?? null;
        if ($controlOptionsArg === null) {
            return null;
        }

        $controlOptionsType = $scope->getType($controlOptionsArg->value);
        $controlOptions = $controlOptionsType->getConstantArrays()[0] ?? null;

        if ($controlOptions === null) {
            return null;
        }

        $options = [];
        foreach ($controlOptions->getKeyTypes() as $keyType) {
            $options[$keyType->getValue()] = $keyType->getValue();
        }
        return $options;
    }
}
