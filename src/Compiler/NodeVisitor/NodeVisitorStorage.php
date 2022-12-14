<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Compiler\NodeVisitor;

use InvalidArgumentException;
use PhpParser\NodeVisitor;

final class NodeVisitorStorage
{
    /** @var array<int, NodeVisitor[]> */
    private array $nodeVisitors = [];

    /** @var array<int, NodeVisitor[]> */
    private array $temporaryNodeVisitors = [];

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

    public function addTemporaryNodeVisitor(int $priority, NodeVisitor $nodeVisitor): void
    {
        if ($priority < 0 || $priority > 10000) {
            throw new InvalidArgumentException('Priority must be set between 0 and 10000');
        }
        if (!isset($this->temporaryNodeVisitors[$priority])) {
            $this->temporaryNodeVisitors[$priority] = [];
        }
        $this->temporaryNodeVisitors[$priority][] = $nodeVisitor;
    }

    /**
     * @return array<int, NodeVisitor[]>
     */
    public function getNodeVisitors(): array
    {
        $allNodeVisitors = $this->nodeVisitors;
        foreach ($this->temporaryNodeVisitors as $priority => $nodeVisitors) {
            if (!isset($allNodeVisitors[$priority])) {
                $allNodeVisitors[$priority] = [];
            }
            foreach ($nodeVisitors as $nodeVisitor) {
                $allNodeVisitors[$priority][] = $nodeVisitor;
            }
        }
        ksort($allNodeVisitors);
        return $allNodeVisitors;
    }

    public function resetTemporaryNodeVisitors(): void
    {
        $this->temporaryNodeVisitors = [];
    }
}
