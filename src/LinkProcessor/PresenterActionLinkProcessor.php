<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\LinkProcessor;

use Nette\Application\InvalidPresenterException;
use PhpParser\Comment\Doc;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Stmt\Expression;

final class PresenterActionLinkProcessor implements LinkProcessorInterface
{
    private PresenterFactoryFaker $presenterFactoryFaker;

    private ?string $actualPresenter = null;

    public function __construct(PresenterFactoryFaker $presenterFactoryFaker)
    {
        $this->presenterFactoryFaker = $presenterFactoryFaker;
    }

    public function setActualPresenter(string $actualPresenter): void
    {
        $this->actualPresenter = $actualPresenter;
    }

    public function check(string $targetName): bool
    {
        return strpos($targetName, ':') !== false;
    }

    /**
     * @param Arg[] $linkParams
     * @param array<string, Doc[]> $attributes
     * @return Expression[]
     */
    public function createLinkExpressions(string $targetName, array $linkParams, array $attributes): array
    {
        $targetNameParts = explode(':', $targetName);
        $actionName = array_pop($targetNameParts);
        $presenterWithModule = implode(':', $targetNameParts);
        $presenterName = implode('', $targetNameParts);
        $presenterVariableName = lcfirst($presenterName) . 'Presenter';
        $presenterFactory = $this->presenterFactoryFaker->getPresenterFactory();
        try {
            $presenterClassName = $presenterFactory->getPresenterClass($presenterWithModule);
        } catch (InvalidPresenterException $e) {
            if ($this->actualPresenter === null) {
                return [];
            }
            $actualPresenter = @$presenterFactory->unformatPresenterClass($this->actualPresenter);
            if ($actualPresenter === null) {
                return [];
            }
            [$moduleName,] = explode(':', $actualPresenter, 2);
            return $this->createLinkExpressions($moduleName . ':' . $targetName, $linkParams, $attributes);
        }

        $variable = new Variable($presenterVariableName);
        $methodNames = $this->prepareMethodNames($presenterClassName, $actionName, $linkParams);

        $attributes['comments'][] = new Doc(
            '/** @var ' . $presenterClassName . ' $' . $presenterVariableName . ' */'
        );

        $expressions = [];
        foreach ($methodNames as $methodName) {
            $expressions[] = new Expression(new MethodCall($variable, $methodName, $linkParams), $attributes);
            $attributes = [];   // reset attributes, we want to print them only with first expression
        }

        return $expressions;
    }

    /**
     * @param Arg[] $linkParams
     * @return string[]
     */
    private function prepareMethodNames(string $presenterClassName, string $actionName, array $linkParams): array
    {
        $methodNames = [];
        $methodExists = false;
        // both methods have to have same parameters, so we check them both if exist
        foreach (['action', 'render'] as $type) {
            $methodName = $type . ucfirst($actionName);
            if (method_exists($presenterClassName, $methodName)) {
                $methodExists = true;
                $methodNames[] = $methodName;
            }
        }

        // If methods not exist, but we pass parameters to links, we need to add method with fake name to find them in CallActionWithParametersMissingCorrespondingMethodErrorTransformer
        if ($methodExists === false && $linkParams !== []) {
            $methodNames[] = $actionName . 'WithParametersMissingCorrespondingMethod';
        }

        return $methodNames;
    }
}
