<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Collector\ValueObject;

use PHPStan\ShouldNotHappenException;

/**
 * @phpstan-type CollectedResolvedNodeArray array{resolver: string, params: array<string, string>}
 */
final class CollectedResolvedNode
{
    private string $resolver;

    /** @var array<string, string> */
    protected array $params;

   /**
    * @param array<string, string> $params
    */
    final public function __construct(string $resolver, array $params)
    {
        $this->resolver = $resolver;
        $this->params = $params;
    }

    public function getResolver(): string
    {
        return $this->resolver;
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
            'params' => $this->params,
        ];
    }

    /**
     * @phpstan-param CollectedResolvedNodeArray $item
     */
    public static function fromArray(array $item): self
    {
        return new CollectedResolvedNode($item['resolver'], $item['params']);
    }
}
