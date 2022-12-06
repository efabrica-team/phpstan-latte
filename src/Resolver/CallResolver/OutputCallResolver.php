<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Resolver\CallResolver;

use Efabrica\PHPStanLatte\Resolver\NameResolver\NameResolver;
use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\Print_;
use PhpParser\Node\Stmt\Echo_;
use PHPStan\Analyser\Scope;

final class OutputCallResolver
{
    private NameResolver $nameResolver;

    public function __construct(NameResolver $nameResolver)
    {
        $this->nameResolver = $nameResolver;
    }

    public function isOutputCallNode(Node $node, Scope $scope): bool
    {
        if ($node instanceof Echo_ || $node instanceof Print_) {
            return true;
        }
        if ($node instanceof FuncCall && $this->nameResolver->resolve($node) === 'print_r' && (
                (count($node->args) < 2) ||
                ($node->args[1] instanceof Arg && $scope->getType($node->args[1]->value)->toBoolean()->isTrue()->no())
        )) {
            return true;
        }
        if ($node instanceof FuncCall && $this->nameResolver->resolve($node) === 'var_dump' && (
                (count($node->args) < 2) ||
                ($node->args[1] instanceof Arg && $scope->getType($node->args[1]->value)->toBoolean()->isTrue()->no())
        )) {
            return true;
        }
        return false;
    }
}
