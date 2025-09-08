<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\LatteContext\CollectedData\Form;

use Efabrica\PHPStanLatte\LatteContext\CollectedData\CollectedLatteContextObject;
use Efabrica\PHPStanLatte\Template\Form\Group;
use PHPStan\PhpDoc\TypeStringResolver;
use ReturnTypeWillChange;

final class CollectedFormGroup extends CollectedLatteContextObject
{
    /** @var class-string */
    private string $className;

    private string $methodName;

    private Group $group;

    /**
     * @param class-string $className
     */
    public function __construct(string $className, string $methodName, Group $group)
    {
        $this->className = $className;
        $this->methodName = $methodName;
        $this->group = $group;
    }

    public function getClassName(): string
    {
        return $this->className;
    }

    public function getMethodName(): string
    {
        return $this->methodName;
    }

    public function getGroup(): Group
    {
        return $this->group;
    }

    /**
     * @return array<string, mixed>
     */
    #[ReturnTypeWillChange]
    public function jsonSerialize(): array
    {
        return [
            'className' => $this->className,
            'methodName' => $this->methodName,
            'group' => $this->group->jsonSerialize(),
        ];
    }

    /**
     * @param array{className: class-string, methodName: string, group: array<string, mixed>} $data
     */
    public static function fromJson(array $data, TypeStringResolver $typeStringResolver): self
    {
        return new self(
            $data['className'],
            $data['methodName'],
            Group::fromJson($data['group'], $typeStringResolver)
        );
    }
}
