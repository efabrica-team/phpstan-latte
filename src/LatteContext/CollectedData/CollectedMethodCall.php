<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\LatteContext\CollectedData;

use PhpParser\Node;
use PhpParser\Node\Stmt\ClassMethod;
use PHPStan\Analyser\Scope;
use PHPStan\PhpDoc\TypeStringResolver;
use PHPStan\Reflection\ClassReflection;
use ReturnTypeWillChange;

final class CollectedMethodCall extends CollectedLatteContextObject
{
    public const CALL = 'call';
    public const TERMINATING_CALL = 'terminating';
    public const OUTPUT_CALL = 'output';

    /** @var ?class-string */
    private ?string $callerClassName;

    private string $callerMethodName;

    private ?string $calledClassName;

    private string $calledMethodName;

    private bool $isCalledConditionally;

    private string $type;

    /** @var array<string, string|int|float|bool> */
    private array $params;

    /** @var ?class-string */
    private ?string $currentClassName;

    /**
     * @param ?class-string $callerClassName
     * @param ?class-string $currentClassName
     * @param array<string, string|int|float|bool> $params
     */
    public function __construct(
        ?string $callerClassName,
        string $callerMethodName,
        ?string $calledClassName,
        string $calledMethodName,
        bool $isCalledConditionally,
        string $type = self::CALL,
        array $params = [],
        ?string $currentClassName = null
    ) {
        $this->callerClassName = $callerClassName;
        $this->callerMethodName = $callerMethodName;
        $this->calledClassName = $calledClassName;
        $this->calledMethodName = $calledMethodName;
        $this->isCalledConditionally = $isCalledConditionally;
        $this->type = $type;
        $this->params = $params;
        $this->currentClassName = $currentClassName;
    }

    /**
     * @return ?class-string
     */
    public function getCallerClassName(): ?string
    {
        return $this->callerClassName;
    }

    public function getCallerMethodName(): string
    {
        return $this->callerMethodName;
    }

    public function getCalledClassName(): ?string
    {
        return $this->calledClassName;
    }

    public function getCalledMethodName(): string
    {
        return $this->calledMethodName;
    }

    public function isCalledConditionally(): bool
    {
        return $this->isCalledConditionally;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function isCall(): bool
    {
        return $this->type === self::CALL;
    }

    public function isTerminatingCall(): bool
    {
        return $this->type === self::TERMINATING_CALL;
    }

    public function isOutputCall(): bool
    {
        return $this->type === self::OUTPUT_CALL;
    }

    /**
     * @return array<string, string|int|float|bool>
     */
    public function getParams(): array
    {
        return $this->params;
    }

    /**
     * @return ?class-string
     */
    public function getCurrentClassName(): ?string
    {
        return $this->currentClassName;
    }

    /**
     * @param class-string $currentClassName
     */
    public function withCurrentClass(ClassReflection $callerReflection, string $currentClassName): self
    {
        if (!in_array($this->calledClassName, ['this', 'self', 'static', 'parent'], true)) {
            return $this;
        }
        if ($this->calledClassName === 'parent') {
            $parentClassReflection = $callerReflection->getParentClass();
            if ($parentClassReflection === null) {
                return $this;
            }
            $calledClassName = $parentClassReflection->getName();
        } elseif ($this->calledClassName === 'self') {
            $calledClassName = $callerReflection->getName();
        } else {
            $calledClassName = $currentClassName;
        }
        return new self(
            $this->callerClassName,
            $this->callerMethodName,
            $calledClassName,
            $this->calledMethodName,
            $this->isCalledConditionally,
            $this->type,
            $this->params,
            $currentClassName
        );
    }

    /**
     * @param array<string, string|int|float|bool> $params
     */
    public static function build(
        Node $node,
        Scope $scope,
        string $calledClassName,
        string $calledMethodName,
        string $type = self::CALL,
        array $params = []
    ): self {
        /** @var Node $parentNode */
        $parentNode = $node->getAttribute('parent') ?? $node;
        /** @var Node $parentNode */
        $grandparentNode = $parentNode->getAttribute('parent') ?? $parentNode;
        return new self(
            $scope->getClassReflection() !== null ? $scope->getClassReflection()->getName() : null,
            $node instanceof ClassMethod ? $node->name->name : $scope->getFunctionName() ?? '',
            $calledClassName,
            $calledMethodName,
            !$grandparentNode instanceof ClassMethod,
            $type,
            $params
        );
    }

    /**
     * @return array<string, mixed>
     */
    #[ReturnTypeWillChange]
    public function jsonSerialize(): array
    {
        return [
            'callerClassName' => $this->callerClassName,
            'callerMethodName' => $this->callerMethodName,
            'calledClassName' => $this->calledClassName,
            'calledMethodName' => $this->calledMethodName,
            'isCalledConditionally' => $this->isCalledConditionally,
            'type' => $this->type,
            'params' => $this->params,
            'currentClassName' => $this->currentClassName,
        ];
    }

    /**
     * @param array{callerClassName?: ?class-string, callerMethodName?: string, calledClassName?: ?class-string, calledMethodName?: string, isCalledConditionally?: bool, type?: string, params?: array<string, string|int|float|bool>, currentClassName?: ?class-string} $data
     */
    public static function fromJson(array $data, TypeStringResolver $typeStringResolver): self
    {
        return new self(
            $data['callerClassName'] ?? null,
            $data['callerMethodName'] ?? '',
            $data['calledClassName'] ?? null,
            $data['calledMethodName'] ?? '',
            $data['isCalledConditionally'] ?? false,
            $data['type'] ?? self::CALL,
            $data['params'] ?? [],
            $data['currentClassName'] ?? null
        );
    }
}
