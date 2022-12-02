<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Collector\ValueObject;

/**
 * @phpstan-import-type CollectedFormFieldArray from CollectedFormField
 * @phpstan-type CollectedFormArray array{className: class-string, methodName: string, name: string, formFields: CollectedFormFieldArray[]}
 */
final class CollectedForm
{
    /** @var class-string */
    private string $className;

    private string $methodName;

    private string $name;

    /** @var CollectedFormField[] */
    private array $formFields;

    /**
     * @param class-string $className
     * @param CollectedFormField[] $formFields
     */
    public function __construct(string $className, string $methodName, string $name, array $formFields)
    {
        $this->className = $className;
        $this->methodName = $methodName;
        $this->name = $name;
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
        return new CollectedForm($item['className'], $item['methodName'], $item['name'], $formFields);
    }
}
