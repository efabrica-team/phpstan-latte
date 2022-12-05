<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\VariableCollector;

use Closure;
use Efabrica\PHPStanLatte\Compiler\Compiler\CompilerInterface;
use Efabrica\PHPStanLatte\Template\Variable;
use Nette\Utils\Strings;
use PHPStan\BetterReflection\BetterReflection;
use PHPStan\Broker\ClassNotFoundException;
use PHPStan\Type\CallableType;
use PHPStan\Type\ClosureTypeFactory;
use PHPStan\Type\ObjectType;

final class DynamicFilterVariables implements VariableCollectorInterface
{
    /** @var array<string, string|array{string, string}|callable> */
    private array $filters;

    private ?ClosureTypeFactory $closureTypeFactory;

    public function __construct(CompilerInterface $compiler, ClosureTypeFactory $closureTypeFactory = null)
    {
        $this->filters = $compiler->getFilters();
        $this->closureTypeFactory = $closureTypeFactory;
    }

    /**
     * @return Variable[]
     */
    public function collect(): array
    {
        $variables = [];
        foreach ($this->filters as $filterName => $filter) {
            if (is_string($filter)) {
                continue;
            }

            if ($filter instanceof Closure) {
                $variableName = '__filter__' . $filterName;
                if ($this->closureTypeFactory) {
                    $variables[$variableName] = new Variable($variableName, $this->closureTypeFactory->fromClosureObject($filter));
                } else {
                    $variables[$variableName] = new Variable($variableName, new CallableType());
                }
                continue;
            }

            if (!is_array($filter)) {
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
