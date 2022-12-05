<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Compiler\NodeVisitor;

use Efabrica\PHPStanLatte\Compiler\LatteVersion;
use Efabrica\PHPStanLatte\Compiler\TypeToPhpDoc;
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

    private TypeToPhpDoc $typeToPhpDoc;

    /**
     * @param Component[] $components
     */
    public function __construct(array $components, TypeToPhpDoc $typeToPhpDoc)
    {
        $this->components = $components;
        $this->typeToPhpDoc = $typeToPhpDoc;
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

        $componentNameParts = explode('-', $componentName);
        $component = $this->findComponentByName($this->components, $componentNameParts);
        $componentType = $component !== null ? $this->typeToPhpDoc->toPhpDocString($component->getType()) : 'mixed';
        $safeComponentType = $componentType !== 'mixed' ? $componentType : '\Nette\ComponentModel\IComponent';
        $node->setDocComment(new Doc($originalDocCommentText . "\n" . '/** @var ' . $safeComponentType . ' ' . $tmpVarName . ' */'));

        if ($component === null) {
            $error = new Error('Component with name "' . $componentName . '" probably doesn\'t exist.');
            return [$node, $error->toNode()];
        }

        if ($componentType === 'mixed') {
            $createComponentMethod = 'createComponent' . ucfirst($componentName);
            $error = new Error(
                'Component with name "' . $componentName . '" have no type specified.',
                'Define return type of ' . $createComponentMethod . ' method.'
            );
            return [$node, $error->toNode()];
        }

        return [$node];
    }

    /**
     * @param Component[] $components
     * @param string[] $componentNameParts
     */
    private function findComponentByName(array $components, array $componentNameParts): ?Component
    {
        $componentNamePart = array_shift($componentNameParts);
        foreach ($components as $component) {
            if ($component->getName() !== $componentNamePart) {
                continue;
            }
            if (count($componentNameParts) === 0) {
                return $component;
            }
            return $this->findComponentByName($component->getSubcomponents() ?: [], $componentNameParts);
        }
        return null;
    }
}
