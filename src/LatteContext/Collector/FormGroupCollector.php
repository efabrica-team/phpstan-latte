<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\LatteContext\Collector;

use Efabrica\PHPStanLatte\LatteContext\CollectedData\Form\CollectedFormGroup;
use Efabrica\PHPStanLatte\PhpDoc\LattePhpDocResolver;
use Efabrica\PHPStanLatte\Resolver\NameResolver\NameResolver;
use Efabrica\PHPStanLatte\Resolver\ValueResolver\ValueResolver;
use Efabrica\PHPStanLatte\Template\Form\Group;
use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\ObjectType;

/**
 * @extends AbstractLatteContextCollector<CollectedFormGroup>
 */
final class FormGroupCollector extends AbstractLatteContextCollector
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
     * @phpstan-return null|CollectedFormGroup[]
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
        if (!(new ObjectType('Nette\Forms\Form'))->isSuperTypeOf($formType)->yes()) {
            return null;
        }

        if ($this->lattePhpDocResolver->resolveForNode($node, $scope)->isIgnored()) {
            return null;
        }

        $formMethodName = $this->nameResolver->resolve($node->name);
        if ($formMethodName !== 'addGroup') {
            return null;
        }

        $groupNameArg = $node->getArgs()[0] ?? null;
        if ($groupNameArg === null) {
            return null;
        }

        $groupNames = $this->valueResolver->resolveStrings($groupNameArg->value, $scope);
        if ($groupNames === null) {
            return null;
        }

        $formGroups = [];
        foreach ($groupNames as $groupName) {
            $formGroups[] = new CollectedFormGroup(
                $classReflection->getName(),
                $methodName,
                new Group($groupName)
            );
        }
        return $formGroups;
    }
}
