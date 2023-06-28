<?php

declare (strict_types=1);

namespace Efabrica\PHPStanLatte\Analyser;

use Efabrica\PHPStanLatte\LatteContext\CollectedData\CollectedError;
use Efabrica\PHPStanLatte\LatteContext\CollectedData\CollectedLatteContextObject;
use PHPStan\Rules\RuleError;
use PHPStan\Rules\RuleErrorBuilder;

class LatteContextData
{
    /** @var array<RuleError> */
    private array $errors;

    /** @var array<CollectedLatteContextObject> */
    private array $collectedData = [];

    /** @var array<class-string, CollectedLatteContextObject[]> */
    private array $collectedDataByType = [];

    /**
     * @param array<CollectedLatteContextObject> $collectedData
     * @param array<RuleError> $errors
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
     * @return array<RuleError>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * @return array<RuleError>
     */
    public function getCollectedErrors(): array
    {
        $errors = [];
        foreach ($this->getCollectedData(CollectedError::class) as $collectedError) {
            $errors[] = RuleErrorBuilder::message($collectedError->getMessage())->file($collectedError->getFile())->line($collectedError->getLine() ?? -1)->build();
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
