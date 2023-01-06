<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Collector\CollectedData;

use PHPStan\ShouldNotHappenException;

/**
 * @phpstan-type CollectedResolvedNodeArray array{resolver: string, analysedFile: string, params: array<string, string>}
 */
final class CollectedResolvedNode extends CollectedValueObject
{
    private string $resolver;

    private string $analysedFile;

    /** @var array<string, string> */
    protected array $params;

   /**
    * @param array<string, string> $params
    */
    final public function __construct(string $resolver, string $analysedFile, array $params)
    {
        $this->resolver = $resolver;
        $this->analysedFile = $analysedFile;
        $this->params = $params;
    }

    public function getResolver(): string
    {
        return $this->resolver;
    }

    public function getAnalysedFile(): string
    {
        return $this->analysedFile;
    }

  /**
   * @return array<string|string>
   */
    public function getParams(): array
    {
        return $this->params;
    }

    public function getParam(string $name): string
    {
        if (!array_key_exists($name, $this->params)) {
            throw new ShouldNotHappenException("Unkwnown CollectedResolvedNode parameter '$name'");
        }
        return $this->params[$name];
    }

    /**
     * @phpstan-return CollectedResolvedNodeArray
     */
    public function toArray(): array
    {
        return [
            'resolver' => $this->resolver,
            'analysedFile' => $this->analysedFile,
            'params' => $this->params,
        ];
    }

    /**
     * @phpstan-param CollectedResolvedNodeArray $item
     */
    public static function fromArray(array $item): self
    {
        return new CollectedResolvedNode($item['resolver'], $item['analysedFile'], $item['params']);
    }
}
