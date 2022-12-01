<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Error;

use Efabrica\PHPStanLatte\Compiler\LineMapper;
use Efabrica\PHPStanLatte\Error\Error as LatteError;
use Efabrica\PHPStanLatte\Error\Transformer\ErrorTransformerInterface;
use PHPStan\Analyser\Error;
use PHPStan\Rules\RuleError;
use PHPStan\Rules\RuleErrorBuilder;

final class ErrorBuilder
{
    /** @var string[] */
    private array $errorPatternsToIgnore = [
        '/Function __latteCompileError not found./', // do not check fake function used to pass compile errors
        '/PHPStanLatteTemplate/', // errors connected with compiled template class
        '/Method Nette\\\\Application\\\\UI\\\\Renderable::redrawControl\(\) invoked with 2 parameters, 0 required\./', // we will not test latte compiler itself
        '/Method Nette\\\\Application\\\\UI\\\\IRenderable::redrawControl\(\) invoked with 2 parameters, 0 required\./', // we will not test latte compiler itself
        '/Access to private property \$blocks of parent class Latte\\\\Runtime\\\\Template\./', // we will not test latte compiler itself
        '/Parameter \#1 \$array of function extract is passed by reference, so it expects variables only\./', // we will not test latte compiler itself
        '/Parameter \#1 \$var_array of function extract is passed by reference, so it expects variables only\./', // we will not test latte compiler itself
        '/Call to an undefined method Nette\\\\ComponentModel\\\\IComponent::render\(\)\./', # rendering of unknown components
        '/Parameter #1 \$blocks of method Nette\\\\Bridges\\\\ApplicationLatte\\\\SnippetDriver::renderSnippets\(\) expects .*/', # nette internal error
        '/Parameter #3 \$blocks of static method Nette\\\\Bridges\\\\ApplicationLatte\\\\UIRuntime::initialize\(\) expects .*/', # nette internal error
    ];

    /** @var ErrorTransformerInterface[] */
    private array $errorTransformers;

    private LineMapper $lineMapper;

    /**
     * @param string[] $errorPatternsToIgnore
     * @param array<string, string> $applicationMapping
     * @param ErrorTransformerInterface[] $errorTransformers
     */
    public function __construct(
        array $errorPatternsToIgnore,
        array $applicationMapping,
        array $errorTransformers,
        LineMapper $lineMapper
    ) {
        $this->errorPatternsToIgnore = array_merge($this->errorPatternsToIgnore, $errorPatternsToIgnore);
        $this->errorTransformers = $errorTransformers;
        $this->lineMapper = $lineMapper;
        if (count($applicationMapping) === 0) {
            $this->errorPatternsToIgnore[] = '/Cannot load presenter .*/';
        }
    }

    /**
     * @param Error[] $originalErrors
     * @return RuleError[]
     */
    public function buildErrors(array $originalErrors, string $templatePath, ?string $context = null): array
    {
        $errors = [];
        foreach ($originalErrors as $originalError) {
            $error = $this->buildError($originalError, $templatePath, $context);
            if ($error === null) {
                continue;
            }
            $errors[] = $error;
        }
        $this->lineMapper->reset();
        return $errors;
    }

    public function buildError(Error $originalError, string $templatePath, ?string $context = null): ?RuleError
    {
        if ($this->shouldErrorBeIgnored($originalError)) {
            return null;
        }

        $error = new LatteError($originalError->getMessage(), $originalError->getTip());
        $error = $this->transformError($error);

        $ruleErrorBuilder = RuleErrorBuilder::message($error->getMessage())
            ->file($templatePath)
            ->metadata(array_merge($originalError->getMetadata(), ['context' => $context === '' ? null : $context]));
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
