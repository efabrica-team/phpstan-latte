<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Template\Form;

use Efabrica\PHPStanLatte\Helper\FormFieldHelper;
use JsonSerializable;
use PHPStan\Type\Type;
use PHPStan\Type\VerbosityLevel;
use ReturnTypeWillChange;

final class Form implements JsonSerializable
{
    private string $name;

    private Type $type;

    /** @var FormField[] */
    private array $formFields = [];

    /**
     * @param FormField[] $formFields
     */
    public function __construct(string $name, Type $type, array $formFields = [])
    {
        $this->name = $name;
        $this->type = $type;
        foreach ($formFields as $formField) {
            $this->formFields[$formField->getName()] = $formField;
        }
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getType(): Type
    {
        return $this->type;
    }

    public function getTypeAsString(): string
    {
        return $this->type->describe(VerbosityLevel::typeOnly());
    }

    /**
     * @return FormField[]
     */
    public function getFormFields(): array
    {
        return $this->formFields;
    }

    public function getFormField(string $name): ?FormField
    {
        return $this->formFields[$name] ?? null;
    }

    /**
     * @param FormField[] $formFields
     */
    public function withFields(array $formFields): self
    {
        return new self(
            $this->name,
            $this->type,
            FormFieldHelper::union($this->formFields, $formFields)
        );
    }

    #[ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return [
          'name' => $this->name,
          'type' => $this->getTypeAsString(),
          'formFields' => $this->formFields,
        ];
    }
}
