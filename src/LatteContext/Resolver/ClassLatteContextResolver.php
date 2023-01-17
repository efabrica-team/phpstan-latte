<?php

namespace Efabrica\PHPStanLatte\LatteContext\Resolver;

use Efabrica\PHPStanLatte\LatteContext\LatteContext;
use Efabrica\PHPStanLatte\Template\TemplateContext;
use PHPStan\BetterReflection\Reflection\ReflectionClass;
use PHPStan\Type\ObjectType;

class ClassLatteContextResolver implements LatteContextResolverInterface
{
    protected ReflectionClass $reflectionClass;

    protected LatteContext $latteContext;

    public function __construct(ReflectionClass $reflectionClass, LatteContext $latteContext)
    {
        $this->reflectionClass = $reflectionClass;
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

    protected function getTemplateContext(ReflectionClass $reflectionClass, LatteContext $latteContext): TemplateContext
    {
        return new TemplateContext(
            $this->getVariables(),
            $this->getComponents(),
            $this->getForms(),
            $this->getFilters()
        );
    }

    protected function getClassName(): string
    {
        return $this->reflectionClass->getName();
    }

    protected function getClassType(): ObjectType
    {
        return new ObjectType($this->getClassName());
    }
}
