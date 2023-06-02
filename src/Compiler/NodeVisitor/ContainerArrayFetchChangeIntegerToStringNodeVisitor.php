<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Compiler\NodeVisitor;

use Efabrica\PHPStanLatte\Compiler\NodeVisitor\Behavior\ExprTypeNodeVisitorBehavior;
use Efabrica\PHPStanLatte\Compiler\NodeVisitor\Behavior\ExprTypeNodeVisitorInterface;
use PhpParser\Node;
use PhpParser\Node\Expr\ArrayDimFetch;
use PhpParser\Node\Scalar\String_;
use PhpParser\NodeVisitorAbstract;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\ObjectType;

final class ContainerArrayFetchChangeIntegerToStringNodeVisitor extends NodeVisitorAbstract implements ExprTypeNodeVisitorInterface
{
    use ExprTypeNodeVisitorBehavior;

    public function enterNode(Node $node): ?Node
    {
        if (!$node instanceof ArrayDimFetch) {
            return null;
        }

        $varType = $this->getType($node->var);
        if ($varType === null) {
            return null;
        }

        $dimType = $node->dim ? $this->getType($node->dim) : null;
        if ((new ObjectType('Nette\Forms\Container'))->isSuperTypeOf($varType)->yes() && $dimType instanceof ConstantIntegerType) {
            $node->dim = new String_((string)$dimType->getValue());
            return $node;
        }

        return null;
    }
}
