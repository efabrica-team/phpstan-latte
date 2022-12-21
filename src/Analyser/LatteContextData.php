<?php

declare (strict_types=1);

namespace Efabrica\PHPStanLatte\Analyser;

use Efabrica\PHPStanLatte\LatteContext\CollectedData\CollectedLatteContextObject;
use PHPStan\Analyser\Error;

class LatteContextData
{
    /**
     * @var array<Error>
     */
    private array $errors;

    /**
     * @var array<CollectedLatteContextObject>
     */
    private array $collectedData = [];

    /**
     * @var array<class-string, CollectedLatteContextObject[]>
     */
    private array $collectedDataByType = [];

    /**
     * @param array<CollectedLatteContextObject> $collectedData
     * @param array<Error> $errors
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
     * @return array<Error>
     */
    public function getErrors(): array
    {
        return $this->errors;
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
