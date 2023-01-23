<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Compiler\NodeVisitor;

use Efabrica\PHPStanLatte\Compiler\NodeVisitor\Behavior\VariablesNodeVisitorBehavior;
use Efabrica\PHPStanLatte\Compiler\NodeVisitor\Behavior\VariablesNodeVisitorInterface;
use Efabrica\PHPStanLatte\Compiler\TypeToPhpDoc;
use Efabrica\PHPStanLatte\Template\ItemCombinator;
use Efabrica\PHPStanLatte\Template\Variable;
use PhpParser\Comment\Doc;
use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\Variable as VariableExpr;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Expression;
use PhpParser\Node\Stmt\Nop;
use PhpParser\NodeVisitorAbstract;
use PHPStan\PhpDoc\TypeStringResolver;
use PHPStan\Type\ArrayType;
use PHPStan\Type\MixedType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\StringType;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\UnionType;

final class AddVarTypesNodeVisitor extends NodeVisitorAbstract implements VariablesNodeVisitorInterface
{
    use VariablesNodeVisitorBehavior;

    /** @var array<string, string> */
    private array $globalVariables;

    private TypeStringResolver $typeStringResolver;

    private TypeToPhpDoc $typeToPhpDoc;

    /**
     * @param array<string, string> $globalVariables
     */
    public function __construct(array $globalVariables, TypeStringResolver $typeStringResolver, TypeToPhpDoc $typeToPhpDoc)
    {
        $this->globalVariables = $globalVariables;
        $this->typeStringResolver = $typeStringResolver;
        $this->typeToPhpDoc = $typeToPhpDoc;
    }

    public function enterNode(Node $node): ?Node
    {
        if (!$node instanceof ClassMethod) {
            return null;
        }

        foreach ($this->globalVariables as $variable => $type) {
            $this->variables[] = new Variable($variable, $this->typeStringResolver->resolve($type));
        }

        $combinedVariables = ItemCombinator::merge(
            [
                new Variable('baseUrl', new StringType()),
                new Variable('basePath', new StringType()),
                new Variable('ʟ_fi', new ObjectType('Latte\Runtime\FilterInfo')),
                new Variable('ʟ_tag', new ArrayType(new MixedType(), new StringType())),
                new Variable('ʟ_if', new ArrayType(new MixedType(), new MixedType())),
                new Variable('ʟ_ifc', new ArrayType(new MixedType(), new MixedType())),
                new Variable('ʟ_try', new ArrayType(new MixedType(), new MixedType())),
                new Variable('ʟ_loc', new ArrayType(new MixedType(), new MixedType())),
                new Variable('ʟ_tmp', new MixedType()),
                new Variable('ʟ_input', new ObjectType('Nette\Forms\Controls\BaseControl')),
                new Variable('ʟ_label', TypeCombinator::addNull(new UnionType([new ObjectType('Nette\Utils\Html'), new StringType()]))),
                // nette\security bridge
                new Variable('user', new ObjectType('Nette\Security\User')),
            ],
            ItemCombinator::union($this->variables)
        );

        $methodParams = [];
        foreach ($node->params as $param) {
            if ($param->var instanceof VariableExpr && is_string($param->var->name)) {
                $methodParams[] = $param->var->name;
            }
        }

        $variableStatements = [];
        foreach ($combinedVariables as $variable) {
            if (in_array($variable->getName(), $methodParams, true)) {
                continue;
            }
            $prependVarTypesDocBlocks = sprintf(
                '/** @var %s $%s */',
                $this->typeToPhpDoc->toPhpDocString($variable->getType()),
                $variable->getName()
            );

            // doc types node
            $docNop = new Nop();
            $docNop->setDocComment(new Doc($prependVarTypesDocBlocks));

            $variableStatements[] = $docNop;
        }

        $variableStatements[] = new Expression(
            new FuncCall(
                new Name('reset'),
                [
                    new Arg(new VariableExpr('this->params')),
                ]
            )
        );

        $node->stmts = array_merge($variableStatements, (array)$node->stmts);
        return $node;
    }
}
