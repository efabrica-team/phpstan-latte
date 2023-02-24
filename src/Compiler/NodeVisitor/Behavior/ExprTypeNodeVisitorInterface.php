<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Compiler\NodeVisitor\Behavior;

use PhpParser\Node;
use PHPStan\Type\Type;

interface ExprTypeNodeVisitorInterface
{
    public const TYPE_ATTRIBUTE = 'type_from_scope';

    public function getType(Node $node): ?Type;
}
