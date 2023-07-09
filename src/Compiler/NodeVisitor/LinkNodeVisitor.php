<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Compiler\NodeVisitor;

use Efabrica\PHPStanLatte\Compiler\NodeVisitor\Behavior\ActualClassNodeVisitorBehavior;
use Efabrica\PHPStanLatte\Compiler\NodeVisitor\Behavior\ActualClassNodeVisitorInterface;
use Efabrica\PHPStanLatte\Error\Error;
use Efabrica\PHPStanLatte\LinkProcessor\LinkProcessorFactory;
use Efabrica\PHPStanLatte\LinkProcessor\LinkProcessorInterface;
use Efabrica\PHPStanLatte\Resolver\NameResolver\NameResolver;
use Nette\Application\InvalidPresenterException;
use PhpParser\ConstExprEvaluationException;
use PhpParser\ConstExprEvaluator;
use PhpParser\Node;
use PhpParser\Node\Expr\BinaryOp\Identical;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\LNumber;
use PhpParser\Node\Stmt\Echo_;
use PhpParser\Node\Stmt\If_;
use PhpParser\NodeVisitorAbstract;
use Throwable;

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
final class LinkNodeVisitor extends NodeVisitorAbstract implements ActualClassNodeVisitorInterface
{
    use ActualClassNodeVisitorBehavior;

    private NameResolver $nameResolver;

    private LinkProcessorFactory $linkProcessorFactory;

    public function __construct(
        NameResolver $nameResolver,
        LinkProcessorFactory $linkProcessorFactory
    ) {
        $this->nameResolver = $nameResolver;
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

        try {
            $targetName = (new ConstExprEvaluator())->evaluateDirectly($target);
        } catch (ConstExprEvaluationException $e) {
            return null;
        }
        if (!is_string($targetName)) {
            return null;
        }

        $targetName = ltrim($targetName, '/');
        // remove anchor
        $hashPosition = strpos($targetName, '#');
        if ($hashPosition !== false) {
            $targetName = substr($targetName, 0, $hashPosition);
        }

        // ignore links to this, it requires parameters from request which is not available in static analysis
        if ($targetName === 'this') {
            return null;
        }

        $linkProcessor = $this->linkProcessorFactory->create($targetName);
        if (!$linkProcessor instanceof LinkProcessorInterface) {
            return null;
        }

        $linkProcessor->setActualClass($this->actualClass);

        $linkParams = array_slice($linkArgs, 1);
        try {
            $expressions = $linkProcessor->createLinkExpressions($targetName, $linkParams, $attributes);
        } catch (InvalidPresenterException $e) {
            $errorNode = (new Error($e->getMessage()))
                ->setTip('Check if your PHPStan configuration for latte > applicationMapping is correct. See https://github.com/efabrica-team/phpstan-latte/docs/configuration.md#applicationmapping')
                ->toNode();
            $errorNode->setAttributes($attributes);
            return [$errorNode];
        } catch (Throwable $e) {
            $errorNode = (new Error($e->getMessage()))
                ->toNode();
            $errorNode->setAttributes($attributes);
            return [$errorNode];
        }
        if ($expressions === []) {
            return null;
        }

        return [
            new If_(new Identical(new FuncCall(new Name('mt_rand')), new LNumber(0)), [
                'stmts' => $expressions
            ], $attributes),
        ];
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
