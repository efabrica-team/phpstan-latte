<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Compiler\NodeVisitor;

use Efabrica\PHPStanLatte\Compiler\NodeVisitor\Behavior\ExprTypeNodeVisitorInterface;
use Efabrica\PHPStanLatte\Compiler\NodeVisitor\Behavior\ScopeNodeVisitorInterface;
use InvalidArgumentException;
use PhpParser\NodeVisitor;

final class NodeVisitorStorage
{
    private const WITHOUT_SCOPE = 'without_scope';

    private const WITH_SCOPE = 'with_scope';

    /** @var array<string, array<int, NodeVisitor[]>> */
    private array $nodeVisitors = [
        self::WITHOUT_SCOPE => [],
        self::WITH_SCOPE => [],
    ];

    public function addNodeVisitor(int $priority, NodeVisitor $nodeVisitor): void
    {
        if ($priority < 0 || $priority > 10000) {
            throw new InvalidArgumentException('Priority must be set between 0 and 10000');
        }
        $scope = $nodeVisitor instanceof ExprTypeNodeVisitorInterface || $nodeVisitor instanceof ScopeNodeVisitorInterface ? self::WITH_SCOPE : self::WITHOUT_SCOPE;
        if (!isset($this->nodeVisitors[$scope][$priority])) {
            $this->nodeVisitors[$scope][$priority] = [];
        }
        $this->nodeVisitors[$scope][$priority][] = $nodeVisitor;
    }

    /**
     * @return array<int, NodeVisitor[]>
     */
    public function getNodeVisitors(bool $withScope = false): array
    {
        $nodeVisitors = $this->nodeVisitors[$withScope ? self::WITH_SCOPE : self::WITHOUT_SCOPE];
        ksort($nodeVisitors);
        return $nodeVisitors;
    }
}
