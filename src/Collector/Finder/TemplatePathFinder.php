<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Collector\Finder;

use Efabrica\PHPStanLatte\Collector\TemplatePathCollector;
use Efabrica\PHPStanLatte\Collector\ValueObject\CollectedTemplatePath;
use PHPStan\Node\CollectedDataNode;

final class TemplatePathFinder
{
    /**
     * @var array<string, array<string, string[]>>
     */
    private array $collectedTemplatePaths;

    public function __construct(CollectedDataNode $collectedDataNode)
    {
        /** @var CollectedTemplatePath[] $collectedTemplatePaths */
        $collectedTemplatePaths = array_merge(...array_values($collectedDataNode->get(TemplatePathCollector::class)));
        foreach ($collectedTemplatePaths as $collectedTemplatePath) {
            $className = $collectedTemplatePath->getClassName();
            $methodName = $collectedTemplatePath->getMethodName();
            if (!isset($this->collectedTemplatePaths[$className][$methodName])) {
                $this->collectedTemplatePaths[$className][$methodName] = [];
            }
            $this->collectedTemplatePaths[$className][$methodName][] = $collectedTemplatePath->getTemplatePath();
        }
    }

    /**
     * @return string[]
     */
    public function find(string $className, string $methodName): array
    {
        return $this->collectedTemplatePaths[$className][$methodName] ?? [];
    }
}
