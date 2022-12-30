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
use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\ArrayDimFetch;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Echo_;
use PhpParser\Node\Stmt\Return_;
use PhpParser\NodeVisitorAbstract;
use PHPStan\Type\VerbosityLevel;

final class AddFormClassesNodeVisitor extends NodeVisitorAbstract implements FormsNodeVisitorInterface
{
    use FormsNodeVisitorBehavior;

    private NameResolver $nameResolver;

    public function __construct(NameResolver $nameResolver)
    {
        $this->nameResolver = $nameResolver;
    }

    public function beforeTraverse(array $nodes)
    {
        $this->reset();
    }

    public function enterNode(Node $node): ?Node
    {
        if ($node instanceof StaticCall) {
            if ($this->nameResolver->resolve($node) === 'renderFormBegin') {
                // TODO add checks
                /** @var Assign $arg0 */
                $arg0 = $node->args[0]->value;
                /** @var Assign $assign */
                $assign = $arg0->expr;
                /** @var ArrayDimFetch $dimFetch */
                $dimFetch = $assign->expr;
                /** @var String_ $xxx */
                $formNameString = $dimFetch->dim;

                // TODO add method where all checks are done
                $formName = $formNameString->value;

                $formClassName = $this->formClassNames[$formName] ?? null;
                if ($formClassName === null) {
                    return null;
                }
                $this->actualForm = $this->forms[$formName] ?? null;

                $node->args[0] = new Assign(new Variable(new Name('form')), new New_(new Name($formClassName)));
                return $node;
            }
            if ($this->nameResolver->resolve($node) === 'renderFormEnd') {
                $node->args[0] = new Variable(new Name('form'));
                return $node;
            }
        } elseif ($node instanceof Echo_) { // TODO we need to replace not only echo
            if ($node->exprs[0] instanceof MethodCall) {
                $methodCall = $node->exprs[0];
                if ($methodCall->var instanceof ArrayDimFetch) {
                    if ($methodCall->var->dim instanceof String_) {
                        $fieldName = $methodCall->var->dim->value;
                        $formField = $this->actualForm->getFormField($fieldName);

                        if ($formField === null) {
                            $error = new Error('Form field with name "' . $fieldName . '" probably does not exist.');
                            $errorNode = $error->toNode();
                            $errorNode->setAttributes($node->getAttributes());
                            return $errorNode;
                        }

                        // TODO if there are more method calls, this is not working
                        if ($methodCall->var->var instanceof FuncCall) {
                            // TODO use name resolver
                            if ($methodCall->var->var->name->toString() === 'end') {
                                $methodCall->var->var = new Variable(new Name('form'));
                                $node->exprs[0] = $methodCall;
                                return $node;
                            }
                        }
                    }
                }
            }
//            var_dump($node->getStartLine());
//            var_dump(get_class());

//        } elseif ($node instanceof ArrayDimFetch) {
//            if ($node->var instanceof Node\Expr\FuncCall) {
//                // TODO use name resolver
//                if ($node->var->name->toString() === 'end') {
//                    $node->var = new Variable(new Name('form'));
//                    return $node;
//                }
//            }
        }


        return null;
    }

    public function afterTraverse(array $nodes): array
    {
        $componentType = '\Nette\ComponentModel\IComponent';
        foreach ($this->forms as $formName => $form) {
            $className = $this->formClassNames[$formName] ?? null;
            if ($className === null) {
                continue;
            }
            $method = (new Method('offsetGet'))
                ->addParam(new Param('name'))
                ->addStmts([
                    new Return_(
                        new StaticCall(
                            new Name('parent'),
                            new Identifier('offsetGet'),
                            [
                                new Arg(new Variable('name')),
                            ]
                        )
                    ),
                ])
                ->makePublic()
                ->setReturnType($componentType);
            $comment = '@return ' . $componentType;
            foreach ($form->getFormFields() as $formField) {
                $comment = str_replace($componentType, '($name is \'' . $formField->getName() . '\' ? ' . $formField->getTypeAsString() . ' : ' . $componentType . ')', $comment);
            }
            $method->setDocComment('/** ' . $comment . ' */');
            $builderClass = (new Class_($className))->extend($form->getType()->describe(VerbosityLevel::typeOnly()))
                ->addStmts([$method]);
            $nodes[] = $builderClass->getNode();
        }
        return $nodes;
    }
}
