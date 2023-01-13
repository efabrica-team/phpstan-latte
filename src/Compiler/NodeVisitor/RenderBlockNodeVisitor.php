<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Compiler\NodeVisitor;

use Efabrica\PHPStanLatte\Resolver\NameResolver\NameResolver;
use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ArrayItem;
use PhpParser\Node\Expr\BinaryOp\Plus;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Name;
use PhpParser\Node\Param;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\NodeVisitorAbstract;

/**
 * changed output from:
 * <code>
 * $this->renderBlock('my-block', $params)
 * </code>
 *
 * to:
 * <code>
 * $this->blockMy_block($params);
 * </code>
 */
final class RenderBlockNodeVisitor extends NodeVisitorAbstract
{
    private NameResolver $nameResolver;

    /** @var array<string, Param[]>   */
    private array $blockMethods = [];

    public function __construct(NameResolver $nameResolver)
    {
        $this->nameResolver = $nameResolver;
    }

    public function beforeTraverse(array $nodes)
    {
        // reset block methods
        $this->blockMethods = [];

        foreach ($nodes as $node) {
            if (!$node instanceof Class_) {
                continue;
            }
            foreach ($node->stmts as $stmt) {
                if (!$stmt instanceof ClassMethod) {
                    continue;
                }
                $methodName = $this->nameResolver->resolve($stmt);
                if ($methodName === null) {
                    continue;
                }
                if (str_starts_with($methodName, 'block')) {
                    $this->blockMethods[$methodName] = $stmt->params;
                }
            }
        }
        return null;
    }

    public function enterNode(Node $node): ?Node
    {
        if (!$node instanceof MethodCall) {
            return null;
        }

        $methodName = $this->nameResolver->resolve($node->name);
        if ($methodName !== 'renderBlock') {
            return null;
        }

        $blockNameArg = $node->getArgs()[0] ?? null;
        if ($blockNameArg === null) {
            return null;
        }

        if (!$blockNameArg->value instanceof String_) {
            return null;
        }

        $blockName = $blockNameArg->value->value;
        $blockMethodName = 'block' . ucfirst(str_replace('-', '_', $blockName));
        $blockMethodParams = $this->blockMethods[$blockMethodName] ?? null;
        if ($blockMethodParams === null) {
            return null;
        }

        $paramsArg = $node->getArgs()[1] ?? null;
        if ($paramsArg === null) {
            return null;
        }

        if ($paramsArg->value instanceof FuncCall && $this->nameResolver->resolve($paramsArg->value->name) === 'get_defined_vars') {
            // transform renderBlock('content') and similar where the second parameter is function call get_defined_vars()
            return new MethodCall(new Variable('this'), $blockMethodName);
        }

        if (!$paramsArg->value instanceof Plus) {
            return null;
        }

        if (!$paramsArg->value->left instanceof Array_) {
            return null;
        }

        $params = [];
        foreach ($paramsArg->value->left->items as $param) {
            if (!$param instanceof ArrayItem) {
                continue;
            }
            if (!$param->key instanceof String_) {
                $params[] = $param->value;
                continue;
            }
            $params[$param->key->value] = $param->value;
        }

        $methodCallArgs = [];
        foreach ($blockMethodParams as $pos => $blockMethodParam) {
            if (!$blockMethodParam instanceof Param) {
                continue;
            }
            if (!$blockMethodParam->var instanceof Variable) {
                continue;
            }
            $variableName = $this->nameResolver->resolve($blockMethodParam->var->name);
            $methodCallArgs[] = new Arg($params[$pos] ?? $params[$variableName] ?? new New_(new Name('MissingBlockParameter')));
        }

        return new MethodCall(new Variable('this'), $blockMethodName, $methodCallArgs);
    }
}
