<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Compiler\NodeVisitor;

use Efabrica\PHPStanLatte\Compiler\NodeVisitor\Behavior\ActualClassNodeVisitorBehavior;
use Efabrica\PHPStanLatte\Compiler\NodeVisitor\Behavior\ActualClassNodeVisitorInterface;
use Efabrica\PHPStanLatte\Resolver\NameResolver\NameResolver;
use PhpParser\Comment\Doc;
use PhpParser\Node;
use PhpParser\Node\Expr\ArrayDimFetch;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\BinaryOp\Coalesce;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Name;
use PhpParser\Node\Param;
use PhpParser\Node\Scalar\DNumber;
use PhpParser\Node\Scalar\LNumber;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Expression;
use PhpParser\Node\Stmt\Unset_;
use PhpParser\NodeVisitorAbstract;

final class AddParametersForBlockNodeVisitor extends NodeVisitorAbstract implements ActualClassNodeVisitorInterface
{
    use ActualClassNodeVisitorBehavior;

    private NameResolver $nameResolver;

    public function __construct(NameResolver $nameResolver)
    {
        $this->nameResolver = $nameResolver;
    }

    /**
     * @return Node[]|null
     */
    public function leaveNode(Node $node): ?array
    {
        if (!$node instanceof ClassMethod) {
            return null;
        }

        $methodName = $this->nameResolver->resolve($node->name);
        if ($methodName === null) {
            return null;
        }
        if (!str_starts_with($methodName, 'block')) {
            return null;
        }

        $comment = $node->getDocComment();
        if ($comment === null) {
            return null;
        }

        $parameters = [];

        $pattern = '/{define (?<block_name>.*?),? (?<parameters>.*)} on line (?<line>\d+)/';
        preg_match($pattern, $comment->getText(), $match);
        if (isset($match['parameters'])) {
            $parametersPattern = '/(?<type>.*?)\$(?<variable>[[:alnum:]]+)( = (?<default>.*?))?,/';
            preg_match_all($parametersPattern, $match['parameters'] . ',', $parameters);
        } else {
            // process default blocks content etc.
            $pattern = '/{block (?<block_name>.*?)} on line (?<line>\d+)/';
            preg_match($pattern, $comment->getText(), $match);
        }

        $nodeComment = isset($match['line']) ? '/* line ' . $match['line'] . ' */' : '';
        $node->params = [];
        if ($parameters) {
            $nodeComment .= "\n/**\n";
            for ($i = 0; $i < count($parameters[0]); $i++) {
                // Type is always nullable - all params are optional in latte
                $type = ltrim(trim($parameters['type'][$i]), '?');
                $nodeComment .= ' * @param ' . ($type ? '?' . $type . ' ' : '') . '$' . $parameters['variable'][$i] . "\n";

                /** @var string|null $defaultValue */
                $defaultValue = $parameters['default'][$i] ?: null;
                if ($defaultValue !== null && str_starts_with('\'', $defaultValue)) {
                    $default = new String_(trim($defaultValue, '\''));
                } elseif (is_numeric($defaultValue)) {
                    if (str_contains($defaultValue, '.')) {
                        $default = new DNumber(floatval($defaultValue));
                    } else {
                        $default = new LNumber(intval($defaultValue));
                    }
                } else {
                    $default = new ConstFetch(new Name('null'));
                }
                $node->params[] = new Param(new Variable($parameters['variable'][$i]), $default);
            }
            $nodeComment .= '*/';
        }
        $node->setDocComment(new Doc($nodeComment));

        $stmts = (array)$node->stmts;
        $newStmts = [];
        foreach ($stmts as $stmt) {
            if ($this->isAssignVariablesFromLargs($stmt)) {
                // remove assign variables from ʟ_args e.g. $param = $ʟ_args[0] ?? $ʟ_args['param'] ?? null;
                continue;
            } elseif ($this->isUnsetLargs($stmt)) {
                // remove unset($ʟ_args);
                continue;
            }
            $newStmts[] = $stmt;
        }
        $node->stmts = $newStmts;
        return [$node];
    }

    private function isAssignVariablesFromLargs(Stmt $stmt): bool
    {
        if (!($stmt instanceof Expression && $stmt->expr instanceof Assign)) {
            return false;
        }

        if (!$stmt->expr->expr instanceof Coalesce) {
            return false;
        }

        if (!($stmt->expr->expr->left instanceof ArrayDimFetch && $stmt->expr->expr->left->var instanceof Variable)) {
            return false;
        }

        if ($this->nameResolver->resolve($stmt->expr->expr->left->var->name) === 'ʟ_args') {
            return true;
        }

        return false;
    }

    private function isUnsetLargs(Stmt $stmt): bool
    {
        return $stmt instanceof Unset_ && ($stmt->vars[0] ?? null) instanceof Variable && $this->nameResolver->resolve($stmt->vars[0]->name) === 'ʟ_args';
    }
}
