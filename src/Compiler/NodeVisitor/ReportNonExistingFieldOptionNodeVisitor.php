<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Compiler\NodeVisitor;

use Efabrica\PHPStanLatte\Error\Error;
use Efabrica\PHPStanLatte\Resolver\NameResolver\NameResolver;
use Efabrica\PHPStanLatte\Template\Form\ControlInterface;
use Efabrica\PHPStanLatte\Template\Form\Field;
use PhpParser\Node;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ArrayDimFetch;
use PhpParser\Node\Expr\ArrayItem;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Scalar\LNumber;
use PhpParser\Node\Scalar\String_;
use PhpParser\NodeVisitorAbstract;
use PHPStan\Type\ObjectType;

final class ReportNonExistingFieldOptionNodeVisitor extends NodeVisitorAbstract
{
    private NameResolver $nameResolver;

    public function __construct(NameResolver $nameResolver)
    {
        $this->nameResolver = $nameResolver;
    }

    public function leaveNode(Node $node): ?Node
    {
        if (!$node instanceof MethodCall) {
            return null;
        }

        if (!in_array($this->nameResolver->resolve($node), ['getControlPart', 'getLabelPart'], true)) {
            return null;
        }

        /** @var ControlInterface|null $formControl */
        $formControl = $node->var->getAttribute('formControl');
        if (!$formControl instanceof Field) {
            return null;
        }

        if ($formControl->getOptions() === null) {
            return null;
        }

        $formControlType = $formControl->getType();
        if (!((new ObjectType('Nette\Forms\Controls\CheckboxList'))->isSuperTypeOf($formControlType)->yes() || (new ObjectType('Nette\Forms\Controls\RadioList'))->isSuperTypeOf($formControlType)->yes())) {
            return null;
        }

        $methodArg = $node->getArgs()[0] ?? null;
        if ($methodArg === null) {
            return null;
        }

        if (!($methodArg->value instanceof String_ || $methodArg->value instanceof LNumber)) {
            return null;
        }

        $option = $methodArg->value->value;
        if (array_key_exists($option, $formControl->getOptions())) {
            return null;
        }

        $error = new Error('Option "' . $option . '" for control "' . $formControl->getName() . '" probably doesn\'t exist.');
        return new ArrayDimFetch(
            new Array_([
                new ArrayItem($node),
                new ArrayItem($error->toNode()->expr),
            ]),
            new LNumber(0)
        );
    }
}
