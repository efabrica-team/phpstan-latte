<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Compiler\NodeVisitor;

use Efabrica\PHPStanLatte\Compiler\NodeVisitor\Behavior\ActualClassNodeVisitorBehavior;
use Efabrica\PHPStanLatte\Resolver\NameResolver\NameResolver;
use PhpParser\Comment\Doc;
use PhpParser\Node;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Name;
use PhpParser\Node\Param;
use PhpParser\Node\Scalar\DNumber;
use PhpParser\Node\Scalar\LNumber;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Expression;
use PhpParser\NodeVisitorAbstract;

final class AddParametersForBlockNodeVisitor extends NodeVisitorAbstract implements PostCompileNodeVisitorInterface
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

        $pattern = '/{define (?<block_name>.*?), (?<parameters>.*)} on line (?<line>\d+)/';
        preg_match($pattern, $comment->getText(), $match);
        if (!isset($match['parameters'])) {
            return null;
        }

        $nodeComment = '/* line ' . $match['line'] . ' */';

        $nodes = [];

        $parametersPattern = '/(?<type>.*?)\$(?<variable>[[:alnum:]]+)( = (?<default>.*?))?,/';
        preg_match_all($parametersPattern, $match['parameters'] . ',', $parameters);

        if ($parameters) {
            $nodeComment .= "\n/**\n";
            for ($i = 0; $i < count($parameters[0]); $i++) {
                // Type is always nullable - all params are optional in latte
                $type = ltrim(trim($parameters['type'][$i]), '?');
                $nodeComment .= ' * @param ' . ($type ? '?' . $type . ' ' : '') . '$' . $parameters['variable'][$i] . "\n";

                /** @var string|null $defaultValue */
                $defaultValue = $parameters['default'][$i] ?: null;
                if ($defaultValue !== null && str_starts_with('\'', $defaultValue)) {
                    $default = new String_(str_replace('\'', '', $defaultValue));
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
            if ($stmt instanceof Expression && $stmt->expr instanceof Assign) {
                continue;
            }
            $newStmts[] = $stmt;
        }
        $node->stmts = $newStmts;

        $nodes[] = $node;
        return $nodes;
    }
}
