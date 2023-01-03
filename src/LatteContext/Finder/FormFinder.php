<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\LatteContext\Finder;

use Efabrica\PHPStanLatte\Analyser\LatteContextData;
use Efabrica\PHPStanLatte\LatteContext\CollectedData\CollectedForm;
use PHPStan\BetterReflection\BetterReflection;

final class FormFinder
{
    /**
     * @var array<string, array<string, CollectedForm[]>>
     */
    private array $collectedForms = [];

    private MethodCallFinder $methodCallFinder;

    public function __construct(LatteContextData $latteContext, MethodCallFinder $methodCallFinder)
    {
        $this->methodCallFinder = $methodCallFinder;

        $collectedForms = $latteContext->getCollectedData(CollectedForm::class);
        foreach ($collectedForms as $collectedForm) {
            $className = $collectedForm->getClassName();
            $methodName = $collectedForm->getMethodName();
            if (!isset($this->collectedForms[$className][$methodName])) {
                $this->collectedForms[$className][$methodName] = [];
            }
            $this->collectedForms[$className][$methodName][] = $collectedForm;
        }
    }

    /**
     * @return CollectedForm[]
     */
    public function find(string $className, string $methodName): array
    {
        return array_merge(
            $this->collectedForms[$className][''] ?? [],
            $this->findInParents($className),
            $this->findInMethodCalls($className, '__construct'),
            $this->findInMethodCalls($className, $methodName),
        );
    }

    /**
     * @return CollectedForm[]
     */
    private function findInParents(string $className): array
    {
        $classReflection = (new BetterReflection())->reflector()->reflectClass($className);

        $collectedForms = [];
        foreach ($classReflection->getParentClassNames() as $parentClass) {
            $collectedForms = array_merge(
                $this->collectedForms[$parentClass][''] ?? [],
                $collectedForms
            );
        }
        return $collectedForms;
    }

    /**
     * @param array<string, array<string, true>> $alreadyFound
     * @return CollectedForm[]
     */
    private function findInMethodCalls(string $className, string $methodName, array &$alreadyFound = []): array
    {
        $declaringClass = $this->methodCallFinder->getDeclaringClass($className, $methodName);
        if (!$declaringClass) {
            return [];
        }

        if (isset($alreadyFound[$declaringClass][$methodName])) {
            return []; // stop recursion
        } else {
            $alreadyFound[$declaringClass][$methodName] = true;
        }

        $collectedForms = [
            $this->collectedForms[$declaringClass][$methodName] ?? [],
        ];

        $methodCalls = $this->methodCallFinder->findCalled($className, $methodName);
        foreach ($methodCalls as $calledMethod) {
            $collectedForms[] = $this->findInMethodCalls($calledMethod->getCalledClassName(), $calledMethod->getCalledMethodName(), $alreadyFound);
        }

        return array_merge(...$collectedForms);
    }
}
