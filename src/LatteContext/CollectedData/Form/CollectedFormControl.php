<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\LatteContext\CollectedData\Form;

use Efabrica\PHPStanLatte\LatteContext\CollectedData\CollectedLatteContextObject;
use Efabrica\PHPStanLatte\Template\Form\ControlInterface;

final class CollectedFormControl extends CollectedLatteContextObject
{
    /** @var class-string */
    private string $className;

    private string $methodName;

    private ControlInterface $formControl;

    /**
     * @param class-string $className
     */
    public function __construct(string $className, string $methodName, ControlInterface $formControl)
    {
        $this->className = $className;
        $this->methodName = $methodName;
        $this->formControl = $formControl;
    }

    public function getClassName(): string
    {
        return $this->className;
    }

    public function getMethodName(): string
    {
        return $this->methodName;
    }

    public function getFormControl(): ControlInterface
    {
        return $this->formControl;
    }
}
