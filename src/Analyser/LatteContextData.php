<?php

declare (strict_types=1);

namespace Efabrica\PHPStanLatte\Analyser;

use Efabrica\PHPStanLatte\LatteContext\CollectedData\CollectedError;
use Efabrica\PHPStanLatte\LatteContext\CollectedData\CollectedLatteContextObject;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\RuleErrorBuilder;

final class LatteContextData
{
    /** @var list<IdentifierRuleError> */
    private array $errors;

    /** @var array<CollectedLatteContextObject> */
    private array $collectedData = [];

    /** @var array<class-string, CollectedLatteContextObject[]> */
    private array $collectedDataByType = [];

    /**
     * @param array<CollectedLatteContextObject> $collectedData
     * @param list<IdentifierRuleError> $errors
     */
    public function __construct(array $collectedData, array $errors)
    {
        foreach ($collectedData as $collectedItem) {
            $this->collectedData[] = $collectedItem;
            $this->collectedDataByType[get_class($collectedItem)][] = $collectedItem;
        }
        $this->errors = $errors;
    }

    /**
     * @return list<IdentifierRuleError>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * @return list<IdentifierRuleError>
     */
    public function getCollectedErrors(): array
    {
        $errors = [];
        foreach ($this->getCollectedData(CollectedError::class) as $collectedError) {
            $errors[] = RuleErrorBuilder::message($collectedError->getMessage())
                ->identifier('latte.error')
                ->file($collectedError->getFile())
                ->line($collectedError->getLine() ?? -1)
                ->build();
        }
        return $errors;
    }

    /**
     * @return array<CollectedLatteContextObject>
     */
    public function getAllCollectedData(): array
    {
        return $this->collectedData;
    }

    /**
     * @template T of CollectedLatteContextObject
     * @param class-string<T> $type
     * @return T[]
     */
    public function getCollectedData(string $type): array
    {
        /** @var T[] */
        return $this->collectedDataByType[$type] ?? [];
    }
}
