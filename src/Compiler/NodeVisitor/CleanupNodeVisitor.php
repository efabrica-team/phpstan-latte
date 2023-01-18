<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Compiler\NodeVisitor;

use Efabrica\PHPStanLatte\Resolver\NameResolver\NameResolver;
use PhpParser\Node;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Stmt\Else_;
use PhpParser\Node\Stmt\Expression;
use PhpParser\Node\Stmt\If_;
use PhpParser\Node\Stmt\TryCatch;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;
use PhpParser\PrettyPrinter\Standard;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Analyser\Scope;
use PHPStan\Analyser\ScopeContext;
use PHPStan\Analyser\ScopeFactory;
use PHPStan\Type\ObjectType;
use PHPStan\Type\VerbosityLevel;

final class CleanupNodeVisitor extends NodeVisitorAbstract
{
    private const METHOD_CALL_CALLER_TYPE = 'method_call_caller_type';

    private NameResolver $nameResolver;

    private ScopeFactory $scopeFactory;

    private Standard $printerStandard;

    private NodeScopeResolver $nodeScopeResolver;

    public function __construct(
        NameResolver $nameResolver,
        ScopeFactory $scopeFactory,
        Standard $printerStandard,
        NodeScopeResolver $nodeScopeResolver
    ) {
        $this->nameResolver = $nameResolver;
        $this->scopeFactory = $scopeFactory;
        $this->printerStandard = $printerStandard;
        $this->nodeScopeResolver = clone $nodeScopeResolver;
    }

    public function beforeTraverse(array $nodes)
    {
        $content = $this->printerStandard->prettyPrintFile($nodes);
        $filename = sys_get_temp_dir() . '/phpstan-latte-' . md5($content) . '.php';
        file_put_contents($filename, $content);
        require_once $filename;
        $scope = $this->scopeFactory->create(ScopeContext::create($filename));

        $nodeCallback = function (Node $node, Scope $scope): void {
            if ($node instanceof MethodCall) {
                $type = $scope->getType($node->var);
                $node->setAttribute(self::METHOD_CALL_CALLER_TYPE, $type);
            }
        };

        $this->nodeScopeResolver->processNodes($nodes, $scope, $nodeCallback);
    }

    public function leaveNode(Node $node)
    {
        if ($node instanceof If_) {
            if ($node->cond instanceof Assign) {
                if ($node->cond->expr instanceof MethodCall) {

                    if ($this->nameResolver->resolve($node->cond->expr) !== 'getLabel') {
                        return null;
                    }
                    $type = $node->cond->expr->getAttribute(self::METHOD_CALL_CALLER_TYPE);
                    if (!$type instanceof ObjectType) {
                        return null;
                    }

                    if ($type->isInstanceOf('Nette\Forms\Controls\CheckboxList')->yes()) {
                        return array_merge([new Expression($node->cond)], $node->stmts);
                    }
                }
            }
        }

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
            if (!($node->cond instanceof ConstFetch && $this->nameResolver->resolve($node->cond->name) === 'false')) {
                return null;
            }

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

        return null;
    }
}
