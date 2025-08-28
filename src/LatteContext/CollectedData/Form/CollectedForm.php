<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\LatteContext\CollectedData\Form;

use Efabrica\PHPStanLatte\LatteContext\CollectedData\CollectedLatteContextObject;
use Efabrica\PHPStanLatte\Template\Form\Form;
use PHPStan\PhpDoc\TypeStringResolver;
use PHPStan\Type\Type;
use ReturnTypeWillChange;

final class CollectedForm extends CollectedLatteContextObject
{
    /** @var class-string */
    private string $className;

    private string $methodName;

    /** @var class-string */
    private string $createdClassName;

    private string $createdMethodName;

    private Form $form;

    /**
     * @param class-string $className
     * @param class-string $createdClassName
     */
    public function __construct(string $className, string $methodName, string $createdClassName, string $createdMethodName, Form $form)
    {
        $this->className = $className;
        $this->methodName = $methodName;
        $this->createdClassName = $createdClassName;
        $this->createdMethodName = $createdMethodName;
        $this->form = $form;
    }

    public function getClassName(): string
    {
        return $this->className;
    }

    public function getMethodName(): string
    {
        return $this->methodName;
    }

    /**
     * @return class-string
     */
    public function getCreatedClassName(): string
    {
        return $this->createdClassName;
    }

    public function getCreatedMethodName(): string
    {
        return $this->createdMethodName;
    }

    public function getForm(): Form
    {
        return $this->form;
    }

    public function withFormType(Type $type): self
    {
        $clone = clone $this;
        $clone->form = $this->form->withType($type);
        return $clone;
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
            'createdClassName' => $this->createdClassName,
            'createdMethodName' => $this->createdMethodName,
            'form' => $this->form->jsonSerialize(),
        ];
    }

    /**
     * @param array{className: class-string, methodName: string, createdClassName: class-string, createdMethodName: string, form: array<string, mixed>} $data
     */
    public static function fromJson(array $data, TypeStringResolver $typeStringResolver): self
    {
        return new self(
            $data['className'],
            $data['methodName'],
            $data['createdClassName'],
            $data['createdMethodName'],
            Form::fromJson($data['form'], $typeStringResolver)
        );
    }
}
