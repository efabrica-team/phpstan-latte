<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Compiler\NodeVisitor;

use InvalidArgumentException;
use PhpParser\NodeVisitor;

final class NodeVisitorStorage
{
    /** @var array<int, NodeVisitor[]> */
    private array $nodeVisitors = [];

    public function addNodeVisitor(int $priority, NodeVisitor $nodeVisitor): void
    {
        if ($priority < 0 || $priority > 10000) {
            throw new InvalidArgumentException('Priority must be set between 0 and 10000');
        }
        if (!isset($this->nodeVisitors[$priority])) {
            $this->nodeVisitors[$priority] = [];
        }
        $this->nodeVisitors[$priority][] = $nodeVisitor;
    }

    /**
     * @return array<int, NodeVisitor[]>
     */
    public function getNodeVisitors(): array
    {
        $nodeVisitors = $this->nodeVisitors;
        ksort($nodeVisitors);
        return $nodeVisitors;
    }
}
