<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\LatteContext\CollectedData;

use Efabrica\PHPStanLatte\Template\Filter;
use PHPStan\Analyser\NameScope;
use PHPStan\PhpDoc\TypeStringResolver;

final class CollectedFilter extends CollectedLatteContextObject
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

    public function jsonSerialize(): array
    {
        return [
            'className' => $this->className,
            'methodName' => $this->methodName,
            'filter' => $this->filter->jsonSerialize(),
        ];
    }

    public static function fromJson(array $data, TypeStringResolver $typeStringResolver): self
    {
        return new self(
            $data['className'],
            $data['methodName'],
            Filter::fromJson($data['filter'], $typeStringResolver)
        );
    }
}
