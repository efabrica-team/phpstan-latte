<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Collector\ValueObject;

use Efabrica\PHPStanLatte\Template\Filter;
use Efabrica\PHPStanLatte\Type\TypeSerializer;

/**
 * @phpstan-type CollectedFilterArray array{className: string, methodName: string, filter: array{name: string, type: array<string, string>}}
 */
final class CollectedFilter extends CollectedValueObject
{
    private string $className;

    private string $methodName;

    private Filter $filter;

    public function __construct(string $className, string $methodName, Filter $filter)
    {
        $this->className = $className;
        $this->methodName = $methodName;
        $this->filter = $filter;
    }

    public function getClassName(): string
    {
        return $this->className;
    }

    public function getMethodName(): string
    {
        return $this->methodName;
    }

    public function getFilter(): Filter
    {
        return $this->filter;
    }

    /**
     * @phpstan-return CollectedFilterArray
     */
    public function toArray(TypeSerializer $typeSerializer): array
    {
        return [
            'className' => $this->className,
            'methodName' => $this->methodName,
            'filter' => [
                'name' => $this->filter->getName(),
                'type' => $typeSerializer->toArray($this->filter->getType()),
            ],
        ];
    }

    /**
     * @phpstan-param CollectedFilterArray $item
     */
    public static function fromArray(array $item, TypeSerializer $typeSerializer): self
    {
        $filter = new Filter($item['filter']['name'], $typeSerializer->fromArray($item['filter']['type']));
        return new CollectedFilter($item['className'], $item['methodName'], $filter);
    }
}
