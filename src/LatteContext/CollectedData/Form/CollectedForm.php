<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\LatteContext\CollectedData\Form;

use Efabrica\PHPStanLatte\LatteContext\CollectedData\CollectedLatteContextObject;
use Efabrica\PHPStanLatte\Template\Form\Form;

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
}
