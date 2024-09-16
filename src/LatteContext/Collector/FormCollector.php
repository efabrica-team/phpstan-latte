<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\LatteContext\Collector;

use Efabrica\PHPStanLatte\LatteContext\CollectedData\Form\CollectedForm;
use Efabrica\PHPStanLatte\PhpDoc\LattePhpDocResolver;
use Efabrica\PHPStanLatte\Resolver\NameResolver\NameResolver;
use Efabrica\PHPStanLatte\Template\Form\Form;
use PhpParser\Node;
use PhpParser\Node\Stmt\ClassMethod;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\ObjectType;

/**
 * @extends AbstractLatteContextCollector<CollectedForm>
 */
final class FormCollector extends AbstractLatteContextCollector
{
    private LattePhpDocResolver $lattePhpDocResolver;

    public function __construct(
        NameResolver $nameResolver,
        ReflectionProvider $reflectionProvider,
        LattePhpDocResolver $lattePhpDocResolver
    ) {
        parent::__construct($nameResolver, $reflectionProvider);
        $this->lattePhpDocResolver = $lattePhpDocResolver;
    }

    public function getNodeTypes(): array
    {
        return [ClassMethod::class];
    }

    /**
     * @param ClassMethod $node
     * @phpstan-return null|non-empty-array<CollectedForm>
     */
    public function collectData(Node $node, Scope $scope): ?array
    {
        $classReflection = $scope->getClassReflection();
        if ($classReflection === null) {
            return null;
        }

        return $this->findCreateComponent($node, $classReflection, $scope);
    }

    /**
     * @phpstan-return null|non-empty-array<CollectedForm>
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
        if (!(new ObjectType('Nette\Forms\Form'))->isSuperTypeOf($returnType)->yes()) {
            return null;
        }

        if ($this->lattePhpDocResolver->resolveForNode($node, $scope)->isIgnored()) {
            return null;
        }

        $formName = lcfirst(str_replace('createComponent', '', $methodName));
        return [new CollectedForm(
            $classReflection->getName(),
            '',
            $classReflection->getName(),
            $methodName,
            new Form($formName, $returnType)
        )];
    }
}
