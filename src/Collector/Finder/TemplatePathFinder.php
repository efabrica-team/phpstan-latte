<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Collector\Finder;

use Efabrica\PHPStanLatte\Collector\TemplatePathCollector;
use Efabrica\PHPStanLatte\Collector\ValueObject\CollectedTemplatePath;
use Nette\Utils\Finder;
use Nette\Utils\Strings;
use PHPStan\BetterReflection\BetterReflection;
use PHPStan\BetterReflection\Reflection\ReflectionMethod;
use PHPStan\Node\CollectedDataNode;

/**
 * @phpstan-import-type CollectedTemplatePathArray from CollectedTemplatePath
 */
final class TemplatePathFinder
{
    /**
     * @var array<string, array<string, array<?string>>>
     */
    private array $collectedTemplatePaths = [];

    private MethodCallFinder $methodCallFinder;

    public function __construct(CollectedDataNode $collectedDataNode, MethodCallFinder $methodCallFinder)
    {
        $this->methodCallFinder = $methodCallFinder;

        $collectedTemplatePaths = $this->buildData(array_filter(array_merge(...array_values($collectedDataNode->get(TemplatePathCollector::class)))));
        foreach ($collectedTemplatePaths as $collectedTemplatePath) {
            $className = $collectedTemplatePath->getClassName();
            $methodName = $collectedTemplatePath->getMethodName();
            if (!isset($this->collectedTemplatePaths[$className][$methodName])) {
                $this->collectedTemplatePaths[$className][$methodName] = [];
            }
            $templatePath = $collectedTemplatePath->getTemplatePath();
            if ($templatePath !== null && strpos($templatePath, '*') !== false) {
                $dirWithoutWildcards = (string)Strings::before((string)Strings::before($templatePath, '*'), '/', -1);
                $pattern = substr($templatePath, strlen($dirWithoutWildcards) + 1);
                /** @var string $file */
                foreach (Finder::findFiles($pattern)->from($dirWithoutWildcards) as $file) {
                    $this->collectedTemplatePaths[$className][$methodName][] = (string)$file;
                }
            } else {
                $this->collectedTemplatePaths[$className][$methodName][] = $templatePath;
            }
        }
    }

    /**
     * @return array<?string>
     */
    public function find(string $className, string $methodName): array
    {
        return array_merge(
            $this->collectedTemplatePaths[$className][''] ?? [],
            $this->findInParents($className),
            $this->findInMethodCalls($className, '__construct'),
            $this->findInMethodCalls($className, $methodName),
        );
    }

    /**
     * @return array<?string>
     */
    public function findByMethod(ReflectionMethod $method): array
    {
        return $this->find($method->getDeclaringClass()->getName(), $method->getName());
    }

    /**
     * @return array<?string>
     */
    private function findInParents(string $className)
    {
        $classReflection = (new BetterReflection())->reflector()->reflectClass($className);

        $collectedTemplatePaths = [];
        foreach ($classReflection->getParentClassNames() as $parentClass) {
            $collectedTemplatePaths = array_merge(
                $this->collectedTemplatePaths[$parentClass][''] ?? [],
                $collectedTemplatePaths
            );
        }
        return $collectedTemplatePaths;
    }

    /**
     * @param array<string, array<string, true>> $alreadyFound
     * @return array<?string>
     */
    private function findInMethodCalls(string $className, string $methodName, array &$alreadyFound = []): array
    {
        if (isset($alreadyFound[$className][$methodName])) {
            return []; // stop recursion
        } else {
            $alreadyFound[$className][$methodName] = true;
        }

        $collectedTemplatePaths = [
            $this->collectedTemplatePaths[$className][$methodName] ?? [],
        ];

        $methodCalls = $this->methodCallFinder->findCalled($className, $methodName);
        foreach ($methodCalls as $calledClassName => $calledMethods) {
            foreach ($calledMethods as $calledMethod) {
                $collectedTemplatePaths[] = $this->findInMethodCalls($calledClassName, $calledMethod, $alreadyFound);
            }
        }

        return array_merge(...$collectedTemplatePaths);
    }

    /**
     * @phpstan-param array<CollectedTemplatePathArray[]> $data
     * @return CollectedTemplatePath[]
     */
    private function buildData(array $data): array
    {
        $collectedTemplatePaths = [];
        foreach ($data as $itemList) {
            foreach ($itemList as $item) {
                $collectedTemplatePaths[] = CollectedTemplatePath::fromArray($item);
            }
        }
        return $collectedTemplatePaths;
    }
}
