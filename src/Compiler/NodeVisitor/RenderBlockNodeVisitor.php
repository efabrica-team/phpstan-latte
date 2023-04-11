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
 * $this->blockMy_block($param1, $param2, $param3);
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

        $parameters = null;
        if ($paramsArg->value instanceof Plus && $paramsArg->value->left instanceof Array_) {
            // some parameters - plus is used
            $parameters = $paramsArg->value->left->items;
        } elseif ($paramsArg->value instanceof Array_) {
            // no parameters - empty array is used
            $parameters = $paramsArg->value->items;
        }

        if ($parameters === null) {
            return null;
        }

        $params = [];
        foreach ($parameters as $param) {
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
        $blockMethodParamDefaults = [];
        foreach ($blockMethodParams as $pos => $blockMethodParam) {
            if (!$blockMethodParam instanceof Param) {
                continue;
            }
            if (!$blockMethodParam->var instanceof Variable) {
                continue;
            }
            $blockMethodParamDefaults[] = $blockMethodParam->default;
            $variableName = $this->nameResolver->resolve($blockMethodParam->var->name);
            $methodCallArgs[] = new Arg($params[$pos] ?? $params[$variableName] ?? new New_(new Name('MissingBlockParameter')));
        }

        $skipMissingBlockParameter = true;
        $reducedMethodCallArgs = [];
        foreach (array_reverse($methodCallArgs, true) as $pos => $methodCallArg) {
            $methodCallArgValue = $methodCallArg->value;
            if ($methodCallArgValue instanceof New_ && $methodCallArgValue->class instanceof Name && $this->nameResolver->resolve($methodCallArgValue->class) === 'MissingBlockParameter') {
                if ($skipMissingBlockParameter) {
                    // skip all `new MissingBlockParameter` from the end of array
                    continue;
                } elseif (isset($blockMethodParamDefaults[$pos])) {
                    // replace `new MissingBlockParameter` with default value of parameter
                    $reducedMethodCallArgs[] = new Arg($blockMethodParamDefaults[$pos]);
                    continue;
                }
            } else {
                // if some other parameter was used, stop skipping of `new MissingBlockParameter` parameters
                $skipMissingBlockParameter = false;
            }
            $reducedMethodCallArgs[] = $methodCallArg;
        }
        $reducedMethodCallArgs = array_reverse($reducedMethodCallArgs);
        return new MethodCall(new Variable('this'), $blockMethodName, $reducedMethodCallArgs);
    }
}
