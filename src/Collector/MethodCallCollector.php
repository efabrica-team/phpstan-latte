<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Collector;

use Efabrica\PHPStanLatte\Collector\ValueObject\CollectedMethodCall;
use Efabrica\PHPStanLatte\Resolver\NameResolver\NameResolver;
use PhpParser\Node;
use PhpParser\Node\Expr\CallLike;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\Variable;
use PHPStan\Analyser\Scope;
use PHPStan\BetterReflection\BetterReflection;
use PHPStan\BetterReflection\Reflector\Exception\IdentifierNotFound;
use PHPStan\Collectors\Collector;
use PHPStan\Type\ObjectType;
use PHPStan\Type\VerbosityLevel;

/**
 * @phpstan-import-type CollectedMethodCallArray from CollectedMethodCall
 * @implements Collector<CallLike, ?CollectedMethodCallArray>
 */
final class MethodCallCollector implements Collector
{
    private NameResolver $nameResolver;

    public function __construct(NameResolver $nameResolver)
    {
        $this->nameResolver = $nameResolver;
    }

    public function getNodeType(): string
    {
        return CallLike::class;
    }

    /**
     * @param CallLike $node
     * @phpstan-return null|CollectedMethodCallArray
     */
    public function processNode(Node $node, Scope $scope): ?array
    {
        $classReflection = $scope->getClassReflection();
        if ($classReflection === null) {
            return null;
        }

        $functionName = $scope->getFunctionName();
        if ($functionName === null) {
            return null;
        }

        $actualClassName = $classReflection->getName();

        if (!$node instanceof MethodCall && !$node instanceof StaticCall) {
            return null;
        }

        if ($node instanceof StaticCall) {
            $calledClassName = $this->nameResolver->resolve($node->class);
            if ($calledClassName === 'parent') {
                $classReflection = $classReflection->getParentClass();
                if ($classReflection === null) {
                    return null;
                }
                $calledClassName = $classReflection->getName();
            }
        } elseif ($node->var instanceof Variable && is_string($node->var->name) && $node->var->name === 'this') {
            $calledClassName = $classReflection->getName();
        } else {
            $callerType = $scope->getType($node->var);
            $calledClassName = $callerType instanceof ObjectType ? $callerType->describe(VerbosityLevel::typeOnly()) : null;
        }

        $calledMethodName = $this->nameResolver->resolve($node->name);
        if ($calledClassName === null || $calledMethodName === null || $calledMethodName === '') {
            return null;
        }

        try {
            $reflectionClass = (new BetterReflection())->reflector()->reflectClass($calledClassName);
        } catch (IdentifierNotFound $e) {
            return null;
        }

        $reflectionMethod = $reflectionClass->getMethod($calledMethodName);
        if ($reflectionMethod === null) {
            return null;
        }

        $calledClassName = $reflectionMethod->getDeclaringClass()->getName();

        // Do not find template variables in nette classes
        if (in_array($calledClassName, [
            'Nette\Application\UI\Presenter',
            'Nette\Application\UI\Control',
            'Nette\Application\UI\Component',
            'Nette\ComponentModel\Container',
            'Latte\Runtime\Template',
        ], true)) {
            return null;
        }

        return (new CollectedMethodCall(
            $actualClassName,
            $functionName,
            $calledClassName,
            $calledMethodName
        ))->toArray();
    }
}
