<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Compiler\NodeVisitor;

use Efabrica\PHPStanLatte\Compiler\NodeVisitor\Behavior\ScopedNodeVisitorBehavior;
use Efabrica\PHPStanLatte\LinkProcessor\LinkProcessorFactory;
use Efabrica\PHPStanLatte\LinkProcessor\LinkProcessorInterface;
use Efabrica\PHPStanLatte\LinkProcessor\PresenterActionLinkProcessor;
use Efabrica\PHPStanLatte\Resolver\NameResolver\NameResolver;
use Efabrica\PHPStanLatte\Resolver\ValueResolver\ValueResolver;
use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ArrayItem;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Stmt\Echo_;
use PhpParser\NodeVisitorAbstract;

/**
 * changed output from:
 * <code>
 * echo LR\Filters::escapeHtmlAttr($this->global->uiPresenter->link("MyPresenter:myAction", [$parameter1, $parameter2]));
 * </code>
 *
 * to:
 * <code>
 * $myPresenter->actionMyActiion($parameter1, $parameter2);
 * $myPresenter->renderMyActiion($parameter1, $parameter2);
 * </code>
 */
final class LinkNodeVisitor extends NodeVisitorAbstract implements PostCompileNodeVisitorInterface
{
    use ScopedNodeVisitorBehavior;

    private NameResolver $nameResolver;

    private ValueResolver $valueResolver;

    private LinkProcessorFactory $linkProcessorFactory;

    public function __construct(
        NameResolver $nameResolver,
        ValueResolver $valueResolver,
        LinkProcessorFactory $linkProcessorFactory
    ) {
        $this->nameResolver = $nameResolver;
        $this->valueResolver = $valueResolver;
        $this->linkProcessorFactory = $linkProcessorFactory;
    }

    /**
     * @return Node[]|null
     */
    public function leaveNode(Node $node): ?array
    {
        if (!$node instanceof Echo_) {
            return null;
        }

        $staticCall = $node->exprs[0] ?? null;
        if (!$staticCall instanceof StaticCall) {
            return null;
        }

        if (count($staticCall->getArgs()) !== 1) {
            return null;
        }

        $arg = $staticCall->getArgs()[0];

        $methodCall = $arg->value;
        if (!$methodCall instanceof MethodCall) {
            return null;
        }

        if (!$this->isMethodCallUiLink($methodCall)) {
            return null;
        }

        return $this->prepareNodes($methodCall, $node->getAttributes());
    }

    /**
     * @param array<string, mixed> $attributes
     * @return Node[]|null
     */
    private function prepareNodes(MethodCall $methodCall, array $attributes): ?array
    {
        $linkArgs = $methodCall->getArgs();
        $target = $linkArgs[0]->value;

        $targetName = $this->valueResolver->resolve($target);
        if (!is_string($targetName)) {
            return null;
        }

        $targetName = ltrim($targetName, '/');

        $linkProcessor = $this->linkProcessorFactory->create($targetName);
        if (!$linkProcessor instanceof LinkProcessorInterface) {
            return null;
        }

        if ($linkProcessor instanceof PresenterActionLinkProcessor && $this->scope->getClassReflection() !== null) {
            $linkProcessor->setActualPresenter($this->scope->getClassReflection()->getName());
        }

        $targetParams = isset($linkArgs[1]) ? $linkArgs[1]->value : null;
        $linkParams = $targetParams instanceof Array_ ? $this->createLinkParams($targetParams) : [];

        $expressions = $linkProcessor->createLinkExpressions($targetName, $linkParams, $attributes);
        if ($expressions === []) {
            return null;
        }

        return $expressions;
    }

    private function isMethodCallUiLink(MethodCall $methodCall): bool
    {
        $methodName = $this->nameResolver->resolve($methodCall->name);
        if ($methodName !== 'link') {
            return false;
        }

        $propertyFetch = $methodCall->var;
        if (!$propertyFetch instanceof PropertyFetch) {
            return false;
        }

        $propertyFetchName = $this->nameResolver->resolve($propertyFetch->name);
        return in_array($propertyFetchName, ['uiControl', 'uiPresenter'], true);
    }

    /**
     * @return Arg[]
     */
    private function createLinkParams(Array_ $array): array
    {
        $linkParams = [];
        foreach ($array->items as $arrayItem) {
            if (!$arrayItem instanceof ArrayItem) {
                continue;
            }

            $linkParams[] = new Arg($arrayItem);
        }

        return $linkParams;
    }
}