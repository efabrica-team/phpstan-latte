<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\VariableCollector;

use Efabrica\PHPStanLatte\Template\Variable;
use Nette\Utils\Strings;
use PHPStan\BetterReflection\BetterReflection;
use PHPStan\Broker\ClassNotFoundException;
use PHPStan\Type\ObjectType;

final class DynamicFilterVariables implements VariableCollectorInterface
{
    /** @var array<string, (string | array{string, string})> */
    private array $filters;

    /**
     * @param array<string, string|array{string, string}> $filters
     */
    public function __construct(array $filters)
    {
        $this->filters = $filters;
    }

    /**
     * @return Variable[]
     */
    public function collect(): array
    {
        $variables = [];
        foreach ($this->filters as $filter) {
            if (is_string($filter)) {
                continue;
            }

            /** @var class-string $className */
            $className = $filter[0];
            $methodName = $filter[1];

            if ($methodName === '') {
                continue;
            }

            try {
                $reflectionClass = (new BetterReflection())->reflector()->reflectClass($className);
                $reflectionMethod = $reflectionClass->getMethod($methodName);

                if ($reflectionMethod === null || $reflectionMethod->isStatic()) {
                    continue;
                }

                // TODO create helper
                $variableName = Strings::firstLower(Strings::replace($className, '#\\\#', '')) . 'Filter';
                $variables[$variableName] = new Variable($variableName, new ObjectType($className));
            } catch (ClassNotFoundException $e) {
                continue;
            }
        }

        return array_values($variables);
    }
}
