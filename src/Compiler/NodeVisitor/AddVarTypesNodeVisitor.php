<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Compiler\NodeVisitor;

use Efabrica\PHPStanLatte\Compiler\NodeVisitor\Behavior\VariablesNodeVisitorBehavior;
use Efabrica\PHPStanLatte\Compiler\NodeVisitor\Behavior\VariablesNodeVisitorInterface;
use Efabrica\PHPStanLatte\Template\ItemCombinator;
use Efabrica\PHPStanLatte\Template\Variable;
use PhpParser\Comment\Doc;
use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\ArrayDimFetch;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\Variable as VariableExpr;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Expression;
use PhpParser\NodeVisitorAbstract;
use PHPStan\PhpDoc\TypeStringResolver;
use PHPStan\PhpDocParser\Ast\ConstExpr\ConstExprStringNode;
use PHPStan\PhpDocParser\Ast\Type\ArrayShapeItemNode;
use PHPStan\PhpDocParser\Ast\Type\ArrayShapeNode;
use PHPStan\Type\ArrayType;
use PHPStan\Type\MixedType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\StringType;
use PHPStan\Type\ThisType;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\UnionType;

final class AddVarTypesNodeVisitor extends NodeVisitorAbstract implements VariablesNodeVisitorInterface
{
    use VariablesNodeVisitorBehavior;

    /** @var array<string, string> */
    private array $globalVariables;

    private TypeStringResolver $typeStringResolver;

    /**
     * @param array<string, string> $globalVariables
     */
    public function __construct(array $globalVariables, TypeStringResolver $typeStringResolver)
    {
        $this->globalVariables = $globalVariables;
        $this->typeStringResolver = $typeStringResolver;
    }

    public function enterNode(Node $node): ?Node
    {
        if (!$node instanceof ClassMethod) {
            return null;
        }

        foreach ($this->globalVariables as $variable => $type) {
            $this->variables[$variable] = new Variable($variable, $this->typeStringResolver->resolve($type));
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

        $arrayShapeItems = [];
        foreach ($combinedVariables as $variable) {
            if (in_array($variable->getName(), $methodParams, true)) {
                continue;
            }

            $variableType = $variable->getType();

            if ($variableType instanceof ThisType) {
                // $this(SomeClass) is transformed to $this, but we want to use SomeClass instead
                $variableType = $variableType->getStaticObjectType();
            }

            $arrayShapeItems[] = new ArrayShapeItemNode(new ConstExprStringNode($variable->getName()), $variable->mightBeUndefined(), $variableType->toPhpDocNode());
        }

        $arrayShape = new ArrayShapeNode($arrayShapeItems);

        $newStatements = [];
        $newStatements[] = new Expression(new Assign(new VariableExpr('__variables__'), new ArrayDimFetch(new PropertyFetch(new VariableExpr('this'), 'params'), new String_('variables'))), [
            'comments' => [
                new Doc('/** @var ' . $arrayShape->__toString() . ' $__variables__ */'),
            ],
        ]);

        $newStatements[] = new Expression(new FuncCall(new Name('extract'), [new Arg(new VariableExpr('__variables__'))]));

        $newStatements[] = new Expression(new Assign(new VariableExpr('__other_variables__'), new ArrayDimFetch(new PropertyFetch(new VariableExpr('this'), 'params'), new String_('other_variables'))), [
            'comments' => [
                new Doc('/** @var array $__other_variables__ */'),
            ],
        ]);
        $newStatements[] = new Expression(new FuncCall(new Name('extract'), [new Arg(new VariableExpr('__other_variables__'))]));
        $node->stmts = array_merge($newStatements, (array)$node->stmts);
        return $node;
    }
}
