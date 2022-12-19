<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\VariableCollector;

use Closure;
use Efabrica\PHPStanLatte\Compiler\Compiler\CompilerInterface;
use Efabrica\PHPStanLatte\Helper\FilterHelper;
use Efabrica\PHPStanLatte\Template\Variable;
use PHPStan\BetterReflection\BetterReflection;
use PHPStan\Broker\ClassNotFoundException;
use PHPStan\PhpDoc\TypeStringResolver;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\CallableType;
use PHPStan\Type\ClosureTypeFactory;
use PHPStan\Type\ObjectType;

final class DynamicFilterVariables implements VariableCollectorInterface
{
    /** @var array<string, string|array{string, string}|array{object, string}|callable> */
    private array $filters;

    /** @var array<string, string|array{string, string}|array{object, string}|callable> */
    private array $tmpFilters = [];

    private TypeStringResolver $typeStringResolver;

    private ?ClosureTypeFactory $closureTypeFactory;

    public function __construct(CompilerInterface $compiler, TypeStringResolver  $typeStringResolver, ClosureTypeFactory $closureTypeFactory = null)
    {
        $this->filters = $compiler->getFilters();
        $this->typeStringResolver = $typeStringResolver;
        $this->closureTypeFactory = $closureTypeFactory;
    }

    /**
     * @param array<string, string|array{string, string}|array{object, string}|callable> $filters
     */
    public function addFilters(array $filters): void
    {
        $this->tmpFilters = array_merge($this->tmpFilters, $filters);
    }

    /**
     * @return Variable[]
     * @throws ShouldNotHappenException
     */
    public function collect(): array
    {
        $variables = [];
        foreach (array_merge($this->filters, $this->tmpFilters) as $filterName => $filter) {
            if (FilterHelper::isCallableString($filter)) {
                $variableName = FilterHelper::createFilterVariableName($filterName);
                /** @var string $filter */
                $variables[$variableName] = new Variable($variableName, $this->typeStringResolver->resolve($filter));
                continue;
            }

            if ($filter instanceof Closure) {
                $variableName = FilterHelper::createFilterVariableName($filterName);
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
            $className = is_string($filter[0]) ? $filter[0] : get_class($filter[0]);
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

                $variableName = FilterHelper::createFilterVariableName($filterName);
                $variables[$variableName] = new Variable($variableName, new ObjectType($className));
            } catch (ClassNotFoundException $e) {
                continue;
            }
        }

        $this->tmpFilters = [];
        return array_values($variables);
    }
}
