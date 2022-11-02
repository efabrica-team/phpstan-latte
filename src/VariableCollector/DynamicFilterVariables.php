<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\VariableCollector;

use Efabrica\PHPStanLatte\Template\Variable;
use Nette\Utils\Strings;
use PHPStan\Type\ObjectType;
use ReflectionClass;
use ReflectionException;

final class DynamicFilterVariables implements VariableCollectorInterface
{
    /** @var array<string, (string | array{string, string})> */
    private array $latteFilters;

    /**
     * @param array<string, string|array{string, string}> $latteFilters
     */
    public function __construct(array $latteFilters)
    {
        $this->latteFilters = $latteFilters;
    }

    /**
     * @return Variable[]
     */
    public function collect(): array
    {
        $variables = [];
        foreach ($this->latteFilters as $latteFilter) {
            if (is_string($latteFilter)) {
                continue;
            }

            /** @var class-string $className */
            $className = $latteFilter[0];
            $methodName = $latteFilter[1];

            try {
                $reflectionClass = new ReflectionClass($className);
                $reflectionMethod = $reflectionClass->getMethod($methodName);

                if ($reflectionMethod->isStatic()) {
                    continue;
                }

                $variableName = Strings::firstLower(Strings::replace($className, '#\\\#', '')) . 'Filter';
                $variables[$variableName] = new Variable($variableName, new ObjectType($className));
            } catch (ReflectionException $e) {
                continue;
            }
        }

        return array_values($variables);
    }
}
