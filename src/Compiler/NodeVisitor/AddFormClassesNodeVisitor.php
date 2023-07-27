<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Compiler\NodeVisitor;

use Efabrica\PHPStanLatte\Compiler\Helper\FormHelper;
use Efabrica\PHPStanLatte\Compiler\NodeVisitor\Behavior\FormsNodeVisitorBehavior;
use Efabrica\PHPStanLatte\Compiler\NodeVisitor\Behavior\FormsNodeVisitorInterface;
use Efabrica\PHPStanLatte\Error\Error;
use Efabrica\PHPStanLatte\Resolver\NameResolver\NameResolver;
use Efabrica\PHPStanLatte\Template\Form\Container;
use Efabrica\PHPStanLatte\Template\Form\ControlHolderInterface;
use Efabrica\PHPStanLatte\Template\Form\ControlInterface;
use Efabrica\PHPStanLatte\Template\Form\Form;
use Efabrica\PHPStanLatte\Template\Form\Group;
use PhpParser\Builder\Class_;
use PhpParser\Builder\Method;
use PhpParser\Builder\Param;
use PhpParser\Comment\Doc;
use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ArrayDimFetch;
use PhpParser\Node\Expr\ArrayItem;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\LNumber;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\Class_ as StmtClass_;
use PhpParser\Node\Stmt\Echo_;
use PhpParser\Node\Stmt\Expression;
use PhpParser\Node\Stmt\If_;
use PhpParser\Node\Stmt\Property;
use PhpParser\Node\Stmt\PropertyProperty;
use PhpParser\Node\Stmt\Return_;
use PhpParser\NodeVisitorAbstract;
use PHPStan\PhpDocParser\Ast\ConstExpr\ConstExprStringNode;
use PHPStan\PhpDocParser\Ast\Type\ArrayShapeItemNode;
use PHPStan\PhpDocParser\Ast\Type\ArrayShapeNode;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\VerbosityLevel;

final class AddFormClassesNodeVisitor extends NodeVisitorAbstract implements FormsNodeVisitorInterface
{
    use FormsNodeVisitorBehavior;

    private const COMPONENT_TYPE = '\Nette\Forms\Controls\BaseControl';

    private const COMPONENT_TYPE_PLACEHOLDER = '%%COMPONENT_TYPE%%';

    private const GROUP_TYPE_PLACEHOLDER = '%%GROUP_TYPE%%';

    private const GROUP_TYPE = '\Nette\Forms\ControlGroup';

    private NameResolver $nameResolver;

    /** @var array<array{node: string, control: string}> */
    private array $errorControlNodes = [];

    /** @var string[] */
    private array $possibleAlwaysTrueLabels = [];

    public function __construct(NameResolver $nameResolver)
    {
        $this->nameResolver = $nameResolver;
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

            if (!$this->isUiControl($dimFetch->var)) {
                return null;
            }

            if (!$dimFetch->dim instanceof String_) {
                return null;
            }

            $formNameString = $dimFetch->dim;
            $formName = $formNameString->value;

            $formClassName = $this->formClassNames[$formName] ?? null;
            if ($formClassName === null) {
                $this->actualForm = new Form($formName, new ObjectType('Nette\Forms\Form'));
                $error = new Error('Form with name "' . $formName . '" probably does not exist.');
                return new Assign(
                    new Variable('form'),
                    new ArrayDimFetch(
                        new Array_([
                            new ArrayItem(new New_(new Name('Nette\Forms\Form'))),
                            new ArrayItem($error->toNode()->expr),
                        ]),
                        new LNumber(0)
                    )
                );
            }

            $this->actualForm = $this->forms[$formName] ?? null;
            return new Assign(new Variable('form'), new StaticCall(new Name(FormHelper::class), 'getForm', [
                new Arg(new ClassConstFetch(new Name($formClassName), 'class')),
            ]));
        } elseif ($node instanceof StaticCall) {
            if ($this->nameResolver->resolve($node->class) === 'Nette\Bridges\FormsLatte\Runtime') {
                if ($this->nameResolver->resolve($node->name) === 'renderFormEnd') {
                    $node->args[0] = new Arg(new Variable('form'));
                    $this->actualForm = null;
                    return $node;
                }

                /**
                 * Replace:
                 * <code>
                 * \Nette\Bridges\FormsLatte\Runtime::item('foobar')
                 * </code>
                 *
                 * With:
                 * <code>
                 * $form['foobar']
                 * <code>
                 *
                 * if foobar exists in actual form
                 */
                if ($this->nameResolver->resolve($node->name) === 'item') {
                    if ($this->actualForm === null) {
                        return null;
                    }
                    $itemArgument = $node->getArgs()[0] ?? null;
                    $itemArgumentValue = $itemArgument ? $itemArgument->value : null;

                    $formControl = null;
                    if ($itemArgumentValue instanceof String_ || $itemArgumentValue instanceof LNumber) {
                        $controlName = (string)$itemArgumentValue->value;
                        // TODO remove when container are supported
                        $controlNameParts = explode('-', $controlName);
                        $controlName = end($controlNameParts);
                        $formControl = $this->actualForm->getControl($controlName);
                        if ($formControl === null) {
                            $this->errorControlNodes[] = [
                                'node' => $this->findParentStmt($node),
                                'control' => $controlName,
                            ];
                            return null;
                        }
                    } elseif ($itemArgumentValue instanceof Variable) {
                        return null;
                    }
                    $attributes = $node->getAttributes();
                    if ($formControl !== null) {
                        $attributes['formControl'] = $formControl;
                    }
                    return new ArrayDimFetch(
                        new Variable('form'),
                        $itemArgumentValue,
                        $attributes
                    );
                }
            }
        } elseif ($node instanceof ArrayDimFetch) {
            if ($this->actualForm === null) {
                return null;
            }

            if (!$node->var instanceof FuncCall) {
                return null;
            }

            if ($this->nameResolver->resolve($node->var) !== 'end') {
                return null;
            }

            if (!$node->var->args[0] instanceof Arg) {
                return null;
            }

            if (!$this->isFormsStack($node->var->args[0]->value)) {
                return null;
            }

            $formControl = null;
            if ($node->dim instanceof String_ || $node->dim instanceof LNumber) {
                $controlName = (string)$node->dim->value;
                // TODO remove when container are supported
                $controlNameParts = explode('-', $controlName);
                $controlName = end($controlNameParts);
                $formControl = $this->actualForm->getControl($controlName);
                if ($formControl === null) {
                    $this->errorControlNodes[] = [
                        'node' => $this->findParentStmt($node),
                        'control' => $controlName,
                    ];
                    return null;
                }

                $formControlType = $formControl->getType();
                if ((new ObjectType('Nette\Forms\Controls\CheckboxList'))->isSuperTypeOf($formControlType)->yes() || (new ObjectType('Nette\Forms\Controls\RadioList'))->isSuperTypeOf($formControlType)->yes()) {
                    $this->possibleAlwaysTrueLabels[] = $this->findParentStmt($node);
                }
            } elseif ($node->dim instanceof Variable) {
                // dynamic control
            } else {
                return null;
            }

            $node->var = new Variable('form');
            if ($formControl !== null) {
                $node->setAttribute('formControl', $formControl);
            }

            return $node;
        }

        return null;
    }

    public function leaveNode(Node $node)
    {
        foreach ($this->errorControlNodes as $errorControlNode) {
            if ($errorControlNode['node'] === spl_object_hash($node)) {
                $error = new Error('Form control with name "' . $errorControlNode['control'] . '" probably does not exist.');
                $errorNode = $error->toNode();
                $errorNode->setAttributes($node->getAttributes());
                return $errorNode;
            }
        }

        /**
         * Replace:
         * <code>
         * if ($ʟ_label = $form["foo"]->getLabel()) {
         *     echo $ʟ_label;
         * }
         * </code>
         *
         * With:
         * <code>
         * $ʟ_label = $form["foo"]->getLabel();
         * echo $ʟ_label;
         * </code>
         *
         * for RadioList and CheckboxList methods getLabel, getLabelPart, getControlPart
         */
        if ($node instanceof If_) {
            foreach ($this->possibleAlwaysTrueLabels as $possibleAlwaysTrueLabel) {
                if ($possibleAlwaysTrueLabel === spl_object_hash($node)) {
                    if ($node->cond instanceof Assign) {
                        if (in_array($this->nameResolver->resolve($node->cond->var), ['ʟ_label', '_label'], true) &&
                            (
                                $node->cond->expr instanceof MethodCall && in_array($this->nameResolver->resolve($node->cond->expr), ['getLabel', 'getLabelPart', 'getControlPart'], true) ||
                                $node->cond->expr instanceof ArrayDimFetch && $node->cond->expr->var instanceof Array_ && isset($node->cond->expr->var->items[0]) && $node->cond->expr->var->items[0]->value instanceof MethodCall && in_array($this->nameResolver->resolve($node->cond->expr->var->items[0]->value), ['getLabel', 'getLabelPart', 'getControlPart'], true)
                            )
                        ) {
                            $expression = new Expression($node->cond);
                            $expression->setAttributes($node->getAttributes());
                            return array_merge([$expression], $node->stmts);
                        }
                    }
                }
            }
        }

        /**
         * Replace:
         * <code>
         * echo \Nette\Bridges\FormsLatte\Runtime::item($name, $this->global)->getControl();
         * </code>
         *
         * With:
         * <code>
         * /** @var Nette\Forms\Controls\BaseControl $ʟ_input
         * $ʟ_input = \Nette\Bridges\FormsLatte\Runtime::item($name, $this->global);
         * echo $ʟ_input->getControl();
         * </code>
         */
        if ($node instanceof Echo_ && ($node->exprs[0] ?? null) instanceof MethodCall) {
            $varName = 'ʟ_input';
            /** @var MethodCall $methodCallExpr */
            $methodCallExpr = $node->exprs[0];
            $methodCallVar = $methodCallExpr->var;
            $methodCalls[] = $methodCallExpr;
            while ($methodCallVar instanceof MethodCall) {
                $methodCalls[] = $methodCallVar;
                $methodCallVar = $methodCallVar->var;
            }

            if ($methodCallVar instanceof StaticCall && $this->nameResolver->resolve($methodCallVar->class) === 'Nette\Bridges\FormsLatte\Runtime' && $this->nameResolver->resolve($methodCallVar->name) === 'item') {
                $newMethodCallVar = new Variable($varName);
                $newMethodCall = null;
                foreach (array_reverse($methodCalls) as $methodCall) {
                    $newMethodCall = $newMethodCallVar = new MethodCall($newMethodCallVar, $methodCall->name, $methodCall->args);
                }

                return [
                    new Expression(new Assign(new Variable($varName), $methodCallVar), ['comments' => [new Doc('/** @var Nette\Forms\Controls\BaseControl $' . $varName . ' */')]]),
                    new Echo_([$newMethodCall]),
                ];
            }
        }

        /**
         * Replace:
         * <code>
         * echo $ʟ_label = \Nette\Bridges\FormsLatte\Runtime::item($name, $this->global)->getLabel();
         * </code>
         *
         * With:
         * <code>
         * /** @var Nette\Forms\Controls\BaseControl $ʟ_label
         * $ʟ_label = \Nette\Bridges\FormsLatte\Runtime::item($name, $this->global);
         * echo $ʟ_label->getLabel();
         * </code>
         */
        if ($node instanceof Echo_ &&
            ($node->exprs[0] ?? null) instanceof Assign &&
            $node->exprs[0]->expr instanceof MethodCall &&
            $node->exprs[0]->var instanceof Variable
        ) {
            $varName = $this->nameResolver->resolve($node->exprs[0]->var->name) ?? '$ʟ_tmp';
            /** @var MethodCall $methodCallExpr */
            $methodCallExpr = $node->exprs[0]->expr;
            $methodCallVar = $methodCallExpr->var;
            $methodCalls[] = $methodCallExpr;
            while ($methodCallVar instanceof MethodCall) {
                $methodCalls[] = $methodCallVar;
                $methodCallVar = $methodCallVar->var;
            }

            if ($methodCallVar instanceof StaticCall && $this->nameResolver->resolve($methodCallVar->class) === 'Nette\Bridges\FormsLatte\Runtime' && $this->nameResolver->resolve($methodCallVar->name) === 'item') {
                $newMethodCallVar = new Variable($varName);
                $newMethodCall = null;
                foreach (array_reverse($methodCalls) as $methodCall) {
                    $newMethodCall = $newMethodCallVar = new MethodCall($newMethodCallVar, $methodCall->name, $methodCall->args);
                }

                return [
                    new Expression(new Assign(new Variable($varName), $methodCallVar), ['comments' => [new Doc('/** @var Nette\Forms\Controls\BaseControl $' . $varName . ' */')]]),
                    new Echo_([$newMethodCall]),
                ];
            }
        }

        /**
         * Replace:
         * <code>
         * end($this->global->formsStack)
         * </code>
         *
         * With
         * <code>
         * $form
         * </code>
         */
        if ($node instanceof FuncCall && $this->nameResolver->resolve($node) === 'end') {
            $arg = $node->getArgs()[0] ?? null;
            if ($arg instanceof Arg && $this->isFormsStack($arg->value)) {
                return new Variable('form');
            }
        }

        return null;
    }

    private function findParentStmt(Node $node): string
    {
        $rootParentNode = $node;
        while (true) {
            $parentNode = $rootParentNode->getAttribute('parent');
            if ($parentNode === null) {
                throw new ShouldNotHappenException('Could not find parent statement.');
            }
            $rootParentNode = $parentNode;
            if ($parentNode instanceof Stmt) {
                break;
            }
        }
        return spl_object_hash($rootParentNode);
    }

    /**
     * @param Node[] $nodes
     * @return Node[]
     */
    public function afterTraverse(array $nodes): array
    {
        $templateClass = false;
        foreach ($nodes as $node) {
            if ($node instanceof StmtClass_) {
                if ($templateClass === false) {
                    // skip Template class
                    $templateClass = true;
                    continue;
                }
                // use Template->global class

                $arrayShapeItems = [];
                foreach ($this->formClassNames as $formName => $form) {
                    $arrayShapeItems[] = new ArrayShapeItemNode(new ConstExprStringNode($formName), false, (new ConstantStringType($form))->toPhpDocNode());
                }
                $arrayShape = new ArrayShapeNode($arrayShapeItems);
                $node->stmts[] = new Property(StmtClass_::MODIFIER_PUBLIC, [
                    new PropertyProperty('formNamesToFormClasses'),
                ], [
                    'comments' => [new Doc('/** @var ' . $arrayShape->__toString() . ' */')],
                ], 'array');
                break;
            }
        }

        $controlAssign = new Expression(new Assign(new Variable('control'), new StaticCall(
            new Name('parent'),
            new Identifier('offsetGet'),
            [
                new Arg(new Variable('name')),
            ]
        )));
        $controlAssign->setDocComment(new Doc('/** @var \Nette\Forms\Controls\BaseControl $control */'));

        $baseOffsetGetMethod = (new Method('offsetGet'))
            ->addParam(new Param('name'))
            ->addStmts([
                $controlAssign,
                new Return_(new Variable('control')),
            ])
            ->makePublic()
            ->setReturnType('Nette\ComponentModel\IComponent');

        // create Container classes
        foreach ($this->forms as $formName => $form) {
            $formClassName = $this->formClassNames[$formName] ?? null;
            if ($formClassName === null) {
                continue;
            }
            foreach ($form->getControls() as $formControl) {
                if (!$formControl instanceof Container) {
                    continue;
                }
                $nodes[] = $this->createClassNode($formClassName, $formControl, $baseOffsetGetMethod);
            }
        }

        // create Form classes
        foreach ($this->forms as $formName => $form) {
            $className = $this->formClassNames[$formName] ?? null;
            if ($className === null) {
                continue;
            }
            $nodes[] = $this->createClassNode($className, $form, $baseOffsetGetMethod);
        }

        return $nodes;
    }

    /**
     * @param Form|Container $controlHolder
     */
    private function createClassNode(string $parentClassName, ControlHolderInterface $controlHolder, Method $baseOffsetGetMethod): Node
    {
        $offsetGetComment = $this->createOffsetGetConditionalReturnTypeComment($parentClassName, $controlHolder->getControls());

        $offsetGetMethod = clone $baseOffsetGetMethod;
        $offsetGetMethod->setDocComment('/** ' . $offsetGetComment . ' */');

        $methods = [
            $offsetGetMethod,
        ];

        $className = $parentClassName;
        if ($controlHolder instanceof Container) {
            $className .= '_' . $controlHolder->getName();
        }

        if ($controlHolder instanceof Form) {
            $getGroupMethod = (new Method('getGroup'))
                ->addParam(new Param('name'))
                ->addStmts([
                    new Return_(new StaticCall(
                        new Name('parent'),
                        new Identifier('getGroup'),
                        [
                            new Arg(new Variable('name')),
                        ]
                    )),
                ])
                ->makePublic()
                ->setReturnType('?Nette\Forms\ControlGroup');

            $getGroupComment = $this->createGetGroupConditionalReturnTypeComment($controlHolder->getGroups());
            $getGroupMethod->setDocComment('/** ' . $getGroupComment . ' */');
            $methods[] = $getGroupMethod;
        }

        $builderClass = (new Class_($className))->extend($controlHolder->getType()->describe(VerbosityLevel::typeOnly()))
            ->addStmts($methods);
        return $builderClass->getNode();
    }

    /**
     * @param ControlInterface[] $controls
     */
    private function createOffsetGetConditionalReturnTypeComment(string $parentName, array $controls): string
    {
        $comment = '@return ' . self::COMPONENT_TYPE_PLACEHOLDER;
        foreach ($controls as $control) {
            if ($control instanceof Container) {
                $controlType = $parentName . '_' . $control->getName();
            } else {
                $controlType = $control->getTypeAsString();
            }

            $comment = str_replace(self::COMPONENT_TYPE_PLACEHOLDER, '($name is \'' . $control->getName() . '\' ? ' . $controlType . ' : ' . self::COMPONENT_TYPE_PLACEHOLDER . ')', $comment);
        }
        return str_replace(self::COMPONENT_TYPE_PLACEHOLDER, self::COMPONENT_TYPE, $comment);
    }

    /**
     * @param Group[] $groups
     */
    private function createGetGroupConditionalReturnTypeComment(array $groups): string
    {
        $comment = '@return ' . self::GROUP_TYPE_PLACEHOLDER;
        foreach ($groups as $group) {
            $comment = str_replace(self::GROUP_TYPE_PLACEHOLDER, '($name is \'' . $group->getName() . '\' ? ' . self::GROUP_TYPE . ' : ' . self::GROUP_TYPE_PLACEHOLDER . ')', $comment);
        }
        return str_replace(self::GROUP_TYPE_PLACEHOLDER, 'null', $comment);
    }

    private function isUiControl(Expr $expr): bool
    {
        if (!$expr instanceof PropertyFetch) {
            return false;
        }

        if (!$expr->name instanceof Identifier) {
            return false;
        }

        if ($this->nameResolver->resolve($expr->name) !== 'uiControl') {
            return false;
        }

        if (!$expr->var instanceof PropertyFetch) {
            return false;
        }

        if (!$expr->var->name instanceof Identifier) {
            return false;
        }

        if ($this->nameResolver->resolve($expr->var->name) !== 'global') {
            return false;
        }

        return true;
    }

    private function isFormsStack(Expr $expr): bool
    {
        if (!$expr instanceof PropertyFetch) {
            return false;
        }

        if (!$expr->name instanceof Identifier) {
            return false;
        }

        if ($this->nameResolver->resolve($expr->name) !== 'formsStack') {
            return false;
        }

        if (!$expr->var instanceof PropertyFetch) {
            return false;
        }

        if (!$expr->var->name instanceof Identifier) {
            return false;
        }

        if ($this->nameResolver->resolve($expr->var->name) !== 'global') {
            return false;
        }

        return true;
    }
}
