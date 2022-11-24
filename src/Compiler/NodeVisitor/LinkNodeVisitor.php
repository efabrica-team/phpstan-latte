<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Compiler\NodeVisitor;

use Efabrica\PHPStanLatte\Compiler\NodeVisitor\Behavior\ActualClassNodeVisitorBehavior;
use Efabrica\PHPStanLatte\LinkProcessor\LinkProcessorFactory;
use Efabrica\PHPStanLatte\LinkProcessor\LinkProcessorInterface;
use Efabrica\PHPStanLatte\Resolver\NameResolver\NameResolver;
use Efabrica\PHPStanLatte\Resolver\ValueResolver\ValueResolver;
use Nette\Application\InvalidPresenterException;
use PhpParser\Node;
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
 * $myPresenter->actionMyAction($parameter1, $parameter2);
 * $myPresenter->renderMyAction($parameter1, $parameter2);
 * </code>
 */
final class LinkNodeVisitor extends NodeVisitorAbstract implements PostCompileNodeVisitorInterface
{
    use ActualClassNodeVisitorBehavior;

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

        $linkProcessor->setActualClass($this->actualClass);

        $linkParams = array_slice($linkArgs, 1);
        try {
            $expressions = $linkProcessor->createLinkExpressions($targetName, $linkParams, $attributes);
        } catch (InvalidPresenterException $e) {
            return null;
        }
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
}
