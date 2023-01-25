<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Compiler\NodeVisitor;

use Efabrica\PHPStanLatte\Resolver\NameResolver\NameResolver;
use InvalidArgumentException;
use PhpParser\Comment\Doc;
use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\BinaryOp\Identical;
use PhpParser\Node\Expr\BooleanNot;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\Throw_;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Else_;
use PhpParser\Node\Stmt\Expression;
use PhpParser\Node\Stmt\If_;
use PhpParser\Node\Stmt\TryCatch;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;

final class CleanupNodeVisitor extends NodeVisitorAbstract
{
    private NameResolver $nameResolver;

    public function __construct(NameResolver $nameResolver)
    {
        $this->nameResolver = $nameResolver;
    }

    public function leaveNode(Node $node)
    {
        if ($node instanceof Expression) {
            // if only one expr in Expression is Variable, we can remove it
            if ($node->expr instanceof Variable) {
                return NodeTraverser::REMOVE_NODE;
            }
        }

        if ($node instanceof TryCatch) {
            // replace function call for closure with closure stmts
            $stmts = $node->stmts;
            if (count($stmts) === 1 && $stmts[0] instanceof Expression) {
                $expression = $stmts[0];
                if (!$expression->expr instanceof FuncCall) {
                    return null;
                }

                if (!$expression->expr->name instanceof Closure) {
                    return null;
                }

                if (count($expression->expr->getArgs()) !== 1) {
                    return null;
                }

                if ($this->nameResolver->resolve($expression->expr->getArgs()[0]->value) !== 'get_defined_vars') {
                    return null;
                }

                /** @var Closure $closure */
                $closure = $expression->expr->name;
                $node->stmts = $closure->stmts;
                return $node;
            }
        }

        if ($node instanceof If_) {
            // replace if (false) to next condition
            if ($node->cond instanceof ConstFetch && $this->nameResolver->resolve($node->cond->name) === 'false') {
                $elseIfs = $node->elseifs;
                $else = $node->else;
                $firstElseIf = array_shift($elseIfs);
                if ($firstElseIf === null) {
                    if (!$else instanceof Else_) {
                        // no elseif nor else
                        return NodeTraverser::REMOVE_NODE;
                    }
                    // only else
                    return $else->stmts;
                }

                $subnodes = [
                    'stmts' => $firstElseIf->stmts,
                    'elseifs' => $elseIfs,
                    'else' => $else,
                ];
                return new If_($firstElseIf->cond, $subnodes, $node->getAttributes());
            }

            // Prevent error: "Call to function is_object() with * will always evaluate to true / false." on dynamic components
            if (($node->cond instanceof FuncCall && $this->nameResolver->resolve($node->cond->name) === 'is_object') ||
                $node->cond instanceof BooleanNot && $node->cond->expr instanceof FuncCall && $this->nameResolver->resolve($node->cond->expr->name) === 'is_object'
            ) {
                $firstStmt = $node->stmts[0] ?? null;

                if ($firstStmt instanceof Expression &&
                    $firstStmt->expr instanceof Assign &&
                    $firstStmt->expr->var instanceof Variable &&
                    in_array($varName = $this->nameResolver->resolve($firstStmt->expr->var), ['_tmp', 'ʟ_tmp'], true)
                ) {
                    $docComment = $node->getDocComment();
                    $docComments = $docComment ? [$docComment->getText()] : [];
                    $docComments[] = '/** @phpstan-ignore-next-line */';
                    $node->setDocComment(new Doc(implode("\n", $docComments)));

                    $nodes = [];
                    if ($node->cond instanceof BooleanNot && $node->cond->expr instanceof FuncCall && isset($node->cond->expr->getArgs()[0])) { // latte 3
                        $nodes[] = new Expression($node->cond->expr->getArgs()[0]->value);
                        $node->cond->expr->args[0] = new Arg(new Variable($varName));
                    }
                    $nodes[] = $node;
                    $nodes[] = new If_(new Identical(new Variable($varName), new ConstFetch(new Name('null'))), [
                        'stmts' => [
                            new Expression(new Throw_(new New_(new Name(InvalidArgumentException::class)))),
                        ],
                    ]);

                    return $nodes;
                }
            }
        }

        return null;
    }
}
