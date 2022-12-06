<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Collector\ValueObject;

use PHPStan\ShouldNotHappenException;
use PHPStan\Type\Type;

/**
 * @phpstan-import-type CollectedFormFieldArray from CollectedFormField
 * @phpstan-type CollectedFormArray array{className: class-string, methodName: string, name: string, type: string, formFields: CollectedFormFieldArray[]}
 */
final class CollectedForm extends CollectedValueObject
{
    /** @var class-string */
    private string $className;

    private string $methodName;

    private string $name;

    private Type $type;

    /** @var CollectedFormField[] */
    private array $formFields;

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
        $this->formFields = $formFields;
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

    /**
     * @phpstan-return CollectedFormArray
     */
    public function toArray(): array
    {
        $formArray = [
            'className' => $this->className,
            'methodName' => $this->methodName,
            'name' => $this->name,
            'type' => serialize($this->type),
            'formFields' => [],
        ];

        foreach ($this->formFields as $formField) {
            $formArray['formFields'][] = $formField->toArray();
        }

        return $formArray;
    }

    /**
     * @phpstan-param CollectedFormArray $item
     */
    public static function fromArray(array $item): self
    {
        $formFields = [];
        foreach ($item['formFields'] as $formField) {
            $formFields[] = CollectedFormField::fromArray($formField);
        }
        $type = unserialize($item['type']);
        if (!$type instanceof Type) {
            throw new ShouldNotHappenException('Cannot unserialize form type');
        }
        return new CollectedForm($item['className'], $item['methodName'], $item['name'], $type, $formFields);
    }
}
