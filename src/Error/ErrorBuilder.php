<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Error;

use Efabrica\PHPStanLatte\Compiler\LineMapper;
use PHPStan\Analyser\Error;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\RuleError;
use PHPStan\Rules\RuleErrorBuilder;

final class ErrorBuilder
{
    private array $errorPatternsToIgnore = [
        '/PHPStanLatteTemplate/',
        '/Method Nette\\\Application\\\UI\\\Renderable::redrawControl\(\) invoked with 2 parameters, 0 required\./',
    ];

    private LineMapper $lineMapper;

    public function __construct(array $errorPatternsToIgnore, LineMapper $lineMapper)
    {
        $this->errorPatternsToIgnore += $errorPatternsToIgnore;
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

    public function buildError(Error $error, string $templatePath, Scope $scope): ?RuleError
    {
        if ($this->shouldErrorBeIgnored($error)) {
            return null;
        }
        $ruleErrorBuilder = RuleErrorBuilder::message($error->getMessage()) // TODO remap messages not registered filters etc.
            ->file($templatePath)
            ->metadata(array_merge($error->getMetadata(), ['context' => $scope->getFile()]));
        if ($error->getLine()) {
            $ruleErrorBuilder->line($this->lineMapper->get($error->getLine()));
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
}
