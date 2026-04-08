<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\LatteContext\Resolver;

use Efabrica\PHPStanLatte\LatteContext\LatteContext;
use Efabrica\PHPStanLatte\Template\TemplateContext;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Type\ObjectType;

class ClassLatteContextResolver implements LatteContextResolverInterface
{
    protected ClassReflection $classReflection;

    protected LatteContext $latteContext;

    public function __construct(ClassReflection $classReflection, LatteContext $latteContext)
    {
        $this->classReflection = $classReflection;
        $this->latteContext = $latteContext;
    }

    public function getVariables(): array
    {
        return $this->latteContext->variableFinder()->find($this->getClassName());
    }

    public function getComponents(): array
    {
        return $this->latteContext->componentFinder()->find($this->getClassName());
    }

    public function getForms(): array
    {
        return $this->latteContext->formFinder()->find($this->getClassName());
    }

    public function getFilters(): array
    {
        return $this->latteContext->filterFinder()->find($this->getClassName());
    }

    public function getTemplateContext(): TemplateContext
    {
        return new TemplateContext(
            $this->getVariables(),
            $this->getComponents(),
            $this->getForms(),
            $this->getFilters()
        );
    }

    /**
     * @return class-string
     */
    protected function getClassName(): string
    {
        return $this->classReflection->getName();
    }

    protected function getClassType(): ObjectType
    {
        return new ObjectType($this->getClassName());
    }
}
