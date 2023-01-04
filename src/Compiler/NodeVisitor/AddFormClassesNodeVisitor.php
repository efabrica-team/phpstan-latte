<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Compiler\NodeVisitor;

use Efabrica\PHPStanLatte\Compiler\NodeVisitor\Behavior\FormsNodeVisitorBehavior;
use Efabrica\PHPStanLatte\Compiler\NodeVisitor\Behavior\FormsNodeVisitorInterface;
use Efabrica\PHPStanLatte\Error\Error;
use Efabrica\PHPStanLatte\Resolver\NameResolver\NameResolver;
use PhpParser\Builder\Class_;
use PhpParser\Builder\Method;
use PhpParser\Builder\Param;
use PhpParser\Comment\Doc;
use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\ArrayDimFetch;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Expression;
use PhpParser\Node\Stmt\Return_;
use PhpParser\NodeVisitorAbstract;
use PHPStan\Type\VerbosityLevel;

final class AddFormClassesNodeVisitor extends NodeVisitorAbstract implements FormsNodeVisitorInterface
{
    use FormsNodeVisitorBehavior;

    private NameResolver $nameResolver;

    /** @var array<array{node: Node, field: string}> */
    private array $errorFieldNodes = [];

    public function __construct(NameResolver $nameResolver)
    {
        $this->nameResolver = $nameResolver;
    }

    public function beforeTraverse(array $nodes)
    {
        $this->resetForms();
        $this->errorFieldNodes = [];
        return null;
    }

    public function enterNode(Node $node): ?Node
    {
        if ($node instanceof Assign) {
            if (!$node->expr instanceof Assign) {
                return null;
            }
            $assign = $node->expr;

            if (!$assign->expr instanceof ArrayDimFetch) {
                return null;
            }
            $dimFetch = $assign->expr;

            if (!$dimFetch->dim instanceof String_) {
                return null;
            }
            $formNameString = $dimFetch->dim;
            $formName = $formNameString->value;

            $formClassName = $this->formClassNames[$formName] ?? null;
            if ($formClassName === null) {
                return null;
            }
            $this->actualForm = $this->forms[$formName] ?? null;
            return new Assign(new Variable('form'), new New_(new Name($formClassName)));
        } elseif ($node instanceof StaticCall) {
            if ($this->nameResolver->resolve($node) === 'renderFormEnd') {
                $node->args[0] = new Arg(new Variable('form'));
                $this->actualForm = null;
                return $node;
            }
        } elseif ($node instanceof ArrayDimFetch) {
            if ($this->actualForm === null) {
                return null;
            }
            if (!$node->dim instanceof String_) {
                return null;
            }

            if (!$node->var instanceof FuncCall) {
                return null;
            }

            if ($this->nameResolver->resolve($node->var) !== 'end') {
                return null;
            }

            $fieldName = $node->dim->value;
            $formField = $this->actualForm->getFormField($fieldName);
            if ($formField === null) {
                $rootParentNode = $node;
                while (true) {
                    $parentNode = $rootParentNode->getAttribute('parent');
                    if ($parentNode === null) {
                        break;
                    }

                    if ($parentNode instanceof ClassMethod) {
                        break;
                    }

                    $rootParentNode = $parentNode;
                }
                $this->errorFieldNodes[] = [
                    'node' => $rootParentNode,
                    'field' => $fieldName,
                ];
                return null;
            }

            $node->var = new Variable('form');
            return $node;
        }

        return null;
    }

    public function leaveNode(Node $node)
    {
        foreach ($this->errorFieldNodes as $errorFieldNode) {
            if ($errorFieldNode['node'] === $node) {
                $error = new Error('Form field with name "' . $errorFieldNode['field'] . '" probably does not exist.');
                $errorNode = $error->toNode();
                $errorNode->setAttributes($node->getAttributes());
                return $errorNode;
            }
        }

        return null;
    }

    /**
     * @param Node[] $nodes
     * @return Node[]
     */
    public function afterTraverse(array $nodes): array
    {
        $componentType = '\Nette\Forms\Controls\BaseControl';
        $componentTypePlaceholder = '%%TYPE%%';
        foreach ($this->forms as $formName => $form) {
            $className = $this->formClassNames[$formName] ?? null;
            if ($className === null) {
                continue;
            }
            $controlAssign = new Expression(new Assign(new Variable('control'), new StaticCall(
                new Name('parent'),
                new Identifier('offsetGet'),
                [
                    new Arg(new Variable('name')),
                ]
            )));
            $controlAssign->setDocComment(new Doc('/** @var \Nette\Forms\Controls\BaseControl $control */'));
            $method = (new Method('offsetGet'))
                ->addParam(new Param('name'))
                ->addStmts([
                    $controlAssign,
                    new Return_(new Variable('control')),
                ])
                ->makePublic()
                ->setReturnType('Nette\ComponentModel\IComponent');
            $comment = '@return ' . $componentTypePlaceholder;
            foreach ($form->getFormFields() as $formField) {
                $comment = str_replace($componentTypePlaceholder, '($name is \'' . $formField->getName() . '\' ? ' . $formField->getTypeAsString() . ' : ' . $componentTypePlaceholder . ')', $comment);
            }
            $comment = str_replace($componentTypePlaceholder, $componentType, $comment);
            $method->setDocComment('/** ' . $comment . ' */');
            $builderClass = (new Class_($className))->extend($form->getType()->describe(VerbosityLevel::typeOnly()))
                ->addStmts([$method]);
            $nodes[] = $builderClass->getNode();
        }
        return $nodes;
    }
}
