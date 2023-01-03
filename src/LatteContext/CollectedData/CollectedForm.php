<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\LatteContext\CollectedData;

use PHPStan\Type\Type;

final class CollectedForm extends CollectedLatteContextObject
{
    /** @var class-string */
    private string $className;

    private string $methodName;

    private string $name;

    private Type $type;

    /** @var CollectedFormField[] */
    private array $formFields = [];

    /**
     * @param class-string $className
     * @param CollectedFormField[] $formFields
     */
    public function __construct(string $className, string $methodName, string $name, Type $type, array $formFields)
    {
        $this->className = $className;
        $this->methodName = $methodName;
        $this->name = $name;
        $this->type = $type;
        foreach ($formFields as $formField) {
            $this->formFields[$formField->getName()] = $formField;
        }
    }

    public function getClassName(): string
    {
        return $this->className;
    }

    public function getMethodName(): string
    {
        return $this->methodName;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getType(): Type
    {
        return $this->type;
    }

    /**
     * @return CollectedFormField[]
     */
    public function getFormFields(): array
    {
        return $this->formFields;
    }

    public function getFormField(string $name): ?CollectedFormField
    {
        return $this->formFields[$name] ?? null;
    }
}
