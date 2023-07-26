<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\LatteContext\CollectedData\Form;

use Efabrica\PHPStanLatte\LatteContext\CollectedData\CollectedLatteContextObject;
use Efabrica\PHPStanLatte\Template\Form\Group;

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
}
