<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Collector\Finder;

use Efabrica\PHPStanLatte\Collector\FormCollector;
use Efabrica\PHPStanLatte\Collector\ValueObject\CollectedForm;
use Efabrica\PHPStanLatte\Type\TypeSerializer;
use PHPStan\BetterReflection\BetterReflection;
use PHPStan\BetterReflection\Reflection\ReflectionMethod;
use PHPStan\Node\CollectedDataNode;

/**
 * @phpstan-import-type CollectedFormArray from CollectedForm
 */
final class FormFinder
{
    /**
     * @var array<string, array<string, CollectedForm[]>>
     */
    private array $collectedForms = [];

    private MethodCallFinder $methodCallFinder;

    public function __construct(CollectedDataNode $collectedDataNode, TypeSerializer $typeSerializer, MethodCallFinder $methodCallFinder)
    {
        $this->methodCallFinder = $methodCallFinder;

        $collectedForms = FormCollector::loadData($collectedDataNode, $typeSerializer, CollectedForm::class);
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
    public function findByMethod(ReflectionMethod $method): array
    {
        return $this->find($method->getDeclaringClass()->getName(), $method->getName());
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
        if (isset($alreadyFound[$className][$methodName])) {
            return []; // stop recursion
        } else {
            $alreadyFound[$className][$methodName] = true;
        }

        $collectedForms = [
            $this->collectedForms[$className][$methodName] ?? [],
        ];

        $methodCalls = $this->methodCallFinder->findCalled($className, $methodName);
        foreach ($methodCalls as $calledClassName => $calledMethods) {
            foreach ($calledMethods as $calledMethod) {
                $collectedForms[] = $this->findInMethodCalls($calledClassName, $calledMethod, $alreadyFound);
            }
        }

        return array_merge(...$collectedForms);
    }
}
