<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Compiler\NodeVisitor;

use Efabrica\PHPStanLatte\Compiler\LatteVersion;
use Efabrica\PHPStanLatte\Error\Error;
use Efabrica\PHPStanLatte\Template\Component;
use PhpParser\Comment\Doc;
use PhpParser\Node;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Expression;
use PhpParser\NodeVisitorAbstract;

/**
 * changed output from:
 * <code>
 * $_tmp = $this->global->uiControl->getComponent("someName");
 * </code>
 *
 * to:
 * <code>
 * /** @var SomeTypeControl $_tmp
 * $_tmp = $this->global->uiControl->getComponent("someName");
 * </code>
 */
final class AddTypeToComponentNodeVisitor extends NodeVisitorAbstract
{
    /** @var Component[] */
    private array $components;

    /**
     * @param Component[] $components
     */
    public function __construct(array $components)
    {
        $this->components = $components;
    }

    /**
     * @return Node[]
     */
    public function leaveNode(Node $node): ?array
    {
        if (!$node instanceof Expression) {
            return null;
        }

        if (!$node->expr instanceof Assign) {
            return null;
        }

        $assign = $node->expr;
        if (!$assign->expr instanceof MethodCall) {
            return null;
        }

        $methodCall = $assign->expr;
        if (!$methodCall->name instanceof Identifier) {
            return null;
        }

        if ($methodCall->name->name !== 'getComponent') {
            return null;
        }

        $componentNameArgument = $methodCall->getArgs()[0] ?? null;
        if ($componentNameArgument === null) {
            return null;
        }

        if (!$componentNameArgument->value instanceof String_) {
            return null;
        }

        $componentName = $componentNameArgument->value->value;

        $originalDocComment = $node->getDocComment();
        $originalDocCommentText = $originalDocComment ? $originalDocComment->getText() : '';

        $tmpVarName = LatteVersion::isLatte2() ? '$_tmp' : '$ʟ_tmp';

        $component = $this->findComponentByName($componentName);
        $componentType = $component !== null ? $component->getTypeAsString() : '\Nette\ComponentModel\IComponent';
        $node->setDocComment(new Doc($originalDocCommentText . "\n" . '/** @var ' . $componentType . ' ' . $tmpVarName . ' */'));

        if ($component === null) {
            return [
                $node,
                (new Error('Component with name "' . $componentName . '" probably doesn\'t exist.'))->toNode(),
            ];
        }

        return [$node];
    }

    private function findComponentByName(string $componentName): ?Component
    {
        foreach ($this->components as $component) {
            if ($component->getName() === $componentName) {
                return $component;
            }
        }
        return null;
    }
}
