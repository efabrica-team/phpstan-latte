<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Compiler\NodeVisitor;

use Efabrica\PHPStanLatte\Resolver\NameResolver\NameResolver;
use PhpParser\Comment\Doc;
use PhpParser\Node;
use PhpParser\Node\Expr\ArrayDimFetch;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\BinaryOp\Coalesce;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Param;
use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Expression;
use PhpParser\Node\Stmt\Function_;
use PhpParser\Node\Stmt\Unset_;
use PhpParser\NodeVisitorAbstract;
use PHPStan\Parser\Parser;
use PHPStan\Parser\ParserErrorsException;

final class AddParametersForBlockNodeVisitor extends NodeVisitorAbstract
{
    private NameResolver $nameResolver;

    private Parser $parser;

    public function __construct(NameResolver $nameResolver, Parser $parser)
    {
        $this->nameResolver = $nameResolver;
        $this->parser = $parser;
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
        $pattern = '/(?<define>{define\s+(?<block_name>.*?)\s*,?\s+(?<parameters>.*)})\s+on line (?<line>\d+)/s';
        preg_match($pattern, $comment->getText(), $match);
        if (isset($match['parameters'])) {
            $define = $match['define'];

            $typesAndVariablesPattern = '/(?<type>[\?\\\[\]\<\>[:alnum:]]*)[ ]*\$(?<variable>[[:alnum:]]+)/s';
            preg_match_all($typesAndVariablesPattern, $match['parameters'], $typesAndVariables);

            $variableTypes = array_combine($typesAndVariables['variable'], $typesAndVariables['type']) ?: [];
            foreach ($variableTypes as $variable => $type) {
                // parameters fallback if parser will fail
                $parameters[$variable] = [
                    'variable' => $variable,
                    'type' => $type,
                    'default' => null,
                ];
                if (str_contains($type, '[]')) {
                    // replace something[] to array because it is not supported by php for now
                    $define = str_replace($type . ' ', 'array ', $define);
                }
            }

            // create php code from latte code of define
            $phpContent = '<?php ' . preg_replace(['/^{define /', '/' . $match['block_name'] . ',? /', '/}$/'], ['function ', $methodName . '(', ') {}'], $define);

            try {
                // parse php code
                $stmts = $this->parser->parseString($phpContent);
                $function = $stmts[0] ?? null;
                if ($function instanceof Function_) {
                    foreach ($function->params as $param) {
                        $variable = $this->nameResolver->resolve($param->var);
                        if ($variable === null) {
                            continue;
                        }
                        $parameters[$variable] = [
                            'variable' => $variable,
                            'type' => $variableTypes[$variable] ?? '',
                            'default' => $param->default,
                        ];
                    }
                }
            } catch (ParserErrorsException $e) {
            }
        } else {
            // process default blocks content etc.
            $pattern = '/{block\s+(?<block_name>.*?)}\s+on line (?<line>\d+)/s';
            preg_match($pattern, $comment->getText(), $match);
        }

        $nodeComment = isset($match['line']) ? '/* line ' . $match['line'] . ' */' : '';
        $node->params = [];
        if ($parameters) {
            $nodeComment .= "\n/**\n";

            foreach ($parameters as $parameter) {
                $type = trim($parameter['type']);
                $nodeComment .= ' * @param ' . ($type ? $type . ' ' : '') . '$' . $parameter['variable'] . "\n";
                $node->params[] = new Param(new Variable($parameter['variable']), $parameter['default']);
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
