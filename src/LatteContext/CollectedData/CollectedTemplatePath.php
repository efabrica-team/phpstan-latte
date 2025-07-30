<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\LatteContext\CollectedData;

use PHPStan\Analyser\NameScope;
use PHPStan\PhpDoc\TypeStringResolver;

final class CollectedTemplatePath extends CollectedLatteContextObject
{
    private string $className;

    private string $methodName;

    private string $templatePath;

    public function __construct(string $className, string $methodName, string $templatePath)
    {
        $this->className = $className;
        $this->methodName = $methodName;
        $this->templatePath = $templatePath;
    }

    public function getClassName(): string
    {
        return $this->className;
    }

    public function getMethodName(): string
    {
        return $this->methodName;
    }

    public function getTemplatePath(): string
    {
        return $this->templatePath;
    }

    public function jsonSerialize(): array
    {
        return [
            'className' => $this->className,
            'methodName' => $this->methodName,
            'templatePath' => $this->templatePath,
        ];
    }

    public static function fromJson(array $data, TypeStringResolver $typeStringResolver): self
    {
        return new self(
            $data['className'],
            $data['methodName'],
            $data['templatePath']
        );
    }
}
