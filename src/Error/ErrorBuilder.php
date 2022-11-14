<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Error;

use Efabrica\PHPStanLatte\Compiler\LineMapper;
use Efabrica\PHPStanLatte\Error\Error as LatteError;
use Efabrica\PHPStanLatte\Error\Transformer\ErrorTransformerInterface;
use PHPStan\Analyser\Error;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\RuleError;
use PHPStan\Rules\RuleErrorBuilder;

final class ErrorBuilder
{
    /** @var string[] */
    private array $errorPatternsToIgnore = [
        '/PHPStanLatteTemplate/',
        '/Method Nette\\\Application\\\UI\\\Renderable::redrawControl\(\) invoked with 2 parameters, 0 required\./',
    ];

    /** @var ErrorTransformerInterface[] */
    private array $errorTransformers;

    private LineMapper $lineMapper;

    /**
     * @param string[] $errorPatternsToIgnore
     * @param ErrorTransformerInterface[] $errorTransformers
     */
    public function __construct(
        array $errorPatternsToIgnore,
        array $errorTransformers,
        LineMapper $lineMapper
    ) {
        $this->errorPatternsToIgnore += $errorPatternsToIgnore;
        $this->errorTransformers = $errorTransformers;
        $this->lineMapper = $lineMapper;
    }

    /**
     * @param Error[] $originalErrors
     * @return RuleError[]
     */
    public function buildErrors(array $originalErrors, string $templatePath, Scope $scope): array
    {
        $errors = [];
        foreach ($originalErrors as $originalError) {
            $error = $this->buildError($originalError, $templatePath, $scope);
            if ($error === null) {
                continue;
            }
            $errors[] = $error;
        }
        $this->lineMapper->reset();
        return $errors;
    }

    public function buildError(Error $originalError, string $templatePath, Scope $scope): ?RuleError
    {
        if ($this->shouldErrorBeIgnored($originalError)) {
            return null;
        }

        $error = new LatteError($originalError->getMessage(), $originalError->getTip());
        $error = $this->transformError($error);

        $ruleErrorBuilder = RuleErrorBuilder::message($error->getMessage())
            ->file($templatePath)
            ->metadata(array_merge($originalError->getMetadata(), ['context' => $scope->getFile()]));
        if ($originalError->getLine()) {
            $ruleErrorBuilder->line($this->lineMapper->get($originalError->getLine()));
        }
        if ($error->getTip()) {
            $ruleErrorBuilder->tip($error->getTip());
        }
        return $ruleErrorBuilder->build();
    }

    private function shouldErrorBeIgnored(Error $error): bool
    {
        foreach ($this->errorPatternsToIgnore as $errorPatternToIgnore) {
            if (preg_match($errorPatternToIgnore, $error->getMessage())) {
                return true;
            }
        }
        return false;
    }

    private function transformError(LatteError $error): LatteError
    {
        foreach ($this->errorTransformers as $errorTransformer) {
            $error = $errorTransformer->transform($error);
        }
        return $error;
    }
}
