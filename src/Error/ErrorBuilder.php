<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Error;

use Efabrica\PHPStanLatte\Error\Error as LatteError;
use Efabrica\PHPStanLatte\Error\LineMapper\LineMap;
use Efabrica\PHPStanLatte\Error\LineMapper\LineMapper;
use Efabrica\PHPStanLatte\Error\Transformer\ErrorTransformerInterface;
use Efabrica\PHPStanLatte\LinkProcessor\PresenterFactoryFaker;
use PHPStan\Analyser\Error;
use PHPStan\Rules\FileRuleError;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\LineRuleError;
use PHPStan\Rules\MetadataRuleError;
use PHPStan\Rules\NonIgnorableRuleError;
use PHPStan\Rules\RuleError;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Rules\TipRuleError;

final class ErrorBuilder
{
    /** @var string[] */
    private array $errorPatternsToIgnore = [
        '/No error to ignore is reported on line .*/',
        '/Function __latteCompileError not found./', // do not check fake function used to pass compile errors
        '/PHPStanLatteTemplate/', // errors connected with compiled template class
        '/Method Nette\\\\Application\\\\UI\\\\Renderable::redrawControl\(\) invoked with 2 parameters, 0 required\./', // we will not test latte compiler itself
        '/Method Nette\\\\Application\\\\UI\\\\IRenderable::redrawControl\(\) invoked with 2 parameters, 0 required\./', // we will not test latte compiler itself
        '/Access to private property \$blocks of parent class Latte\\\\Runtime\\\\Template\./', // we will not test latte compiler itself
        '/Parameter \#1 \$array of function extract is passed by reference, so it expects variables only\./', // we will not test latte compiler itself
        '/Parameter \#1 \$var_array of function extract is passed by reference, so it expects variables only\./', // we will not test latte compiler itself
        '/Call to an undefined method Nette\\\\ComponentModel\\\\IComponent::render\(\)\./', # rendering of unknown components
        '/Parameter #1 \$blocks of method Nette\\\\Bridges\\\\ApplicationLatte\\\\SnippetDriver::renderSnippets\(\) expects .*/', # nette internal error
        '/Parameter #1 \$blocks of method Nette\\\\Bridges\\\\ApplicationLatte\\\\SnippetRuntime::renderSnippets\(\) expects .*/', # nette internal error
        '/Parameter #3 \$blocks of static method Nette\\\\Bridges\\\\ApplicationLatte\\\\UIRuntime::initialize\(\) expects .*/', # nette internal error
        '/Cannot call method getParent\(\) on Latte\\\\Essential\\\\CachingIterator\|null\./', # latte internal error
        '/Cannot call method attributes\(\) on Nette\\\\Utils\\\\Html\|null\./', # latte internal error
        '/Only booleans are allowed in an if condition, bool\|null given\./', // latte code don't pass phpstan/strict-rules
        '/Instanceof between .* and Nette\\\\Application\\\\UI\\\\Renderable will always evaluate to true\./', // latte code don't pass phpstan/strict-rules
        '/Parameter #2 \$parent of class (Latte\\\\Runtime\\\\CachingIterator|Latte\\\\Essential\\\\CachingIterator) constructor expects (Latte\\\\Runtime\\\\CachingIterator|Latte\\\\Essential\\\\CachingIterator)\|null, mixed given\./', // creating caching iterator
        '/Cannot access property (.*?) on (Latte\\\\Runtime\\\\CachingIterator|Latte\\\\Essential\\\\CachingIterator)\|null\./', // inner foreach cause that phpstan thinks there is null in CachingIterator
        '/Cannot call method (.*?) on (Latte\\\\Runtime\\\\CachingIterator|Latte\\\\Essential\\\\CachingIterator)\|null\./', // inner foreach cause that phpstan thinks there is null in CachingIterator
        '/Parameter #3 \$s of static method Latte\\\\Runtime\\\\Filters::convertTo\(\) expects string, mixed given\./', // latte 3 internal error
        '/Cannot call method addAttributes\(\) on Nette\\\\Utils\\\\Html\|string\./', // we will not test latte compiler itself
        '/Cannot call method addAttributes\(\) on Nette\\\\Utils\\\\Html\|null\./', // we will not test latte compiler itself
        '/Instantiated class MissingBlockParameter not found\./', # missing block parameter palceholder
        '/Variable \$ʟ_it on left side of \?\? always exists and is not nullable\./', // $ʟ_it in try / catch in foreach is always set
        '/Variable \$ʟ_it on left side of \?\? is never defined\./', // $ʟ_it in try / catch in foreach is never defined
        '/Cannot call method render\(\) on mixed\./', // redundant error for unknown components with phpstan-nette extension
        '/PHPDoc tag @var for variable \$__variables__ has no value type specified in iterable type array\./', // fake variable $__variables__ can have not specified array type
        '/Cannot call method startTag\(\) on Nette\\\\Utils\\\\Html\|string\./', // nette/forms error https://github.com/nette/forms/issues/308
        '/Cannot call method endTag\(\) on Nette\\\\Utils\\\\Html\|string\./', // nette/forms error https://github.com/nette/forms/issues/308
    ];

    /** @var string[] */
    private array $warningPatterns;

    /** @var ErrorTransformerInterface[] */
    private array $errorTransformers;

    private LineMapper $lineMapper;

    /**
     * @param string[] $errorPatternsToIgnore
     * @param string[] $warningPatterns
     * @param ErrorTransformerInterface[] $errorTransformers
     */
    public function __construct(
        array $errorPatternsToIgnore,
        array $warningPatterns,
        bool $strictMode,
        PresenterFactoryFaker $presenterFactoryFaker,
        array $errorTransformers,
        LineMapper $lineMapper
    ) {
        $this->errorPatternsToIgnore = array_merge($this->errorPatternsToIgnore, $errorPatternsToIgnore);
        $this->warningPatterns = $warningPatterns;
        $this->errorTransformers = $errorTransformers;
        $this->lineMapper = $lineMapper;
        if ($strictMode === false) {
            $this->errorPatternsToIgnore[] = '/Parameter #1 \$destination of method Nette\\\\Application\\\\UI\\\\Component::link\(\) expects string, Latte\\\\Runtime\\\\Html\|string\|false given\./'; // nette/application error https://github.com/nette/application/issues/313 found by https://github.com/efabrica-team/phpstan-latte/issues/398
        }
        if ($presenterFactoryFaker->getPresenterFactory() === null) {
            $this->errorPatternsToIgnore[] = '/Cannot load presenter .*/';
        }
    }

    /**
     * @param Error[] $originalErrors
     * @return RuleError[]
     */
    public function buildErrors(array $originalErrors, string $templatePath, ?string $compiledTemplatePath, ?string $context = null): array
    {
        $errorSignatures = [];
        $errors = [];
        foreach ($originalErrors as $originalError) {
            $error = $this->buildError($originalError, $templatePath, $compiledTemplatePath, $context);
            if ($error === null) {
                continue;
            }
            $errorSignature = $this->errorSignature($error);
            if (isset($errorSignatures[$errorSignature])) {
                continue;
            }
            $errorSignatures[$errorSignature] = true;
            $errors[] = $error;
        }
        return $errors;
    }

    public function buildError(Error $originalError, string $templatePath, ?string $compiledTemplatePath, ?string $context = null): ?RuleError
    {
        $lineMap = $compiledTemplatePath ? $this->lineMapper->getLineMap($compiledTemplatePath) : new LineMap();

        $error = new LatteError($originalError->getMessage(), $originalError->getTip());
        $error = $this->transformError($error);

        $ruleErrorBuilder = RuleErrorBuilder::message($error->getMessage())
            ->file($templatePath)
            ->metadata(array_merge($originalError->getMetadata(), [
                'context' => $context === '' ? null : $context,
                'is_warning' => $this->isWarning($error->getMessage()),
                'compiled_template_path' => $compiledTemplatePath,
                'compiled_template_error_line' => $originalError->getLine(),
            ]));
        if ($originalError->getLine()) {
            $ruleErrorBuilder->line($lineMap->get($originalError->getLine()));
        }
        if ($error->getTip()) {
            $ruleErrorBuilder->tip($error->getTip());
        }
        if ($this->shouldErrorBeIgnored($error->getMessage())) {
            return null;
        }
        return $ruleErrorBuilder->build();
    }

    /**
     * @param RuleError[] $ruleErrors
     * @return RuleError[]
     */
    public function buildRuleErrors(array $ruleErrors): array
    {
        $newRuleErrors = [];
        foreach ($ruleErrors as $ruleError) {
            if ($this->shouldErrorBeIgnored($ruleError->getMessage())) {
                continue;
            }

            $newRuleError = RuleErrorBuilder::message($ruleError->getMessage());

            $metaData = [];
            if ($ruleError instanceof MetadataRuleError) {
                $metaData = $ruleError->getMetadata();
            }
            $metaData['is_warning'] = $this->isWarning($ruleError->getMessage());
            $newRuleError->metadata($metaData);

            if ($ruleError instanceof FileRuleError) {
                $newRuleError->file($ruleError->getFile());
            }

            if ($ruleError instanceof LineRuleError) {
                $newRuleError->line($ruleError->getLine());
            }

            if ($ruleError instanceof TipRuleError) {
                $newRuleError->tip($ruleError->getTip());
            }

            if ($ruleError instanceof IdentifierRuleError) {
                $newRuleError->identifier($ruleError->getIdentifier());
            }

            if ($ruleError instanceof NonIgnorableRuleError) {
                $newRuleError->nonIgnorable();
            }
            $newRuleErrors[] = $newRuleError->build();
        }
        return $newRuleErrors;
    }

    private function errorSignature(RuleError $error): string
    {
        $values = (array)$error;
        unset($values['metadata']);
        return md5((string)json_encode($values));
    }

    private function shouldErrorBeIgnored(string $message): bool
    {
        foreach ($this->errorPatternsToIgnore as $errorPatternToIgnore) {
            if (preg_match($errorPatternToIgnore, $message)) {
                return true;
            }
        }
        return false;
    }

    private function isWarning(string $message): bool
    {
        foreach ($this->warningPatterns as $warningPattern) {
            if (preg_match($warningPattern, $message)) {
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
