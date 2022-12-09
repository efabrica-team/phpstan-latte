<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Collector\ValueObject;

use Efabrica\PHPStanLatte\Type\TypeSerializer;
use PHPStan\Type\Type;

/**
 * @phpstan-import-type CollectedFormFieldArray from CollectedFormField
 * @phpstan-type CollectedFormArray array{className: class-string, methodName: string, name: string, type: array<string, string>, formFields: CollectedFormFieldArray[]}
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
    public function toArray(TypeSerializer $typeSerializer): array
    {
        $formArray = [
            'className' => $this->className,
            'methodName' => $this->methodName,
            'name' => $this->name,
            'type' => $typeSerializer->toArray($this->type),
            'formFields' => [],
        ];

        foreach ($this->formFields as $formField) {
            $formArray['formFields'][] = $formField->toArray($typeSerializer);
        }

        return $formArray;
    }

    /**
     * @phpstan-param CollectedFormArray $item
     */
    public static function fromArray(array $item, TypeSerializer $typeSerializer): self
    {
        $formFields = [];
        foreach ($item['formFields'] as $formField) {
            $formFields[] = CollectedFormField::fromArray($formField, $typeSerializer);
        }
        $type = $typeSerializer->fromArray($item['type']);
        return new CollectedForm($item['className'], $item['methodName'], $item['name'], $type, $formFields);
    }
}
