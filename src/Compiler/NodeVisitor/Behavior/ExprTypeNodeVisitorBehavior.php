<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Compiler\NodeVisitor\Behavior;

use PhpParser\Node;
use PHPStan\Type\Type;

trait ExprTypeNodeVisitorBehavior
{
    public function getType(Node $node): ?Type
    {
        $type = $node->getAttribute(ExprTypeNodeVisitorInterface::TYPE_ATTRIBUTE);
        return $type instanceof Type ? $type : null;
    }
}
