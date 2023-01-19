<?php

namespace Efabrica\PHPStanLatte\LatteContext\Collector;

use PhpParser\Node;

/**
 * @template T
 * @implements LatteContextSubCollectorInterface<T>
 */
abstract class AbstractLatteContextSubCollector implements LatteContextSubCollectorInterface
{
    public function isSupported(Node $node): bool
    {
        foreach ($this->getNodeTypes() as $nodeType) {
            if ($node instanceof $nodeType) {
                return true;
            }
        }
        return false;
    }
}
