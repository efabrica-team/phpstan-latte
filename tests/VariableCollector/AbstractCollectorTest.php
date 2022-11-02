<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Tests\VariableCollector;

use Efabrica\PHPStanLatte\Template\Variable;
use Efabrica\PHPStanLatte\VariableCollector\VariableCollectorInterface;
use PHPStan\Reflection\ReflectionProvider\DummyReflectionProvider;
use PHPStan\Reflection\ReflectionProviderStaticAccessor;
use PHPUnit\Framework\TestCase;

abstract class AbstractCollectorTest extends TestCase
{
    protected function setUp(): void
    {
        ReflectionProviderStaticAccessor::registerInstance(new DummyReflectionProvider());
    }

    final public function testCollector(): void
    {
        $collector = $this->createCollector();
        $variables = $collector->collect();

        $this->assertCount($this->variablesCount(), $variables);
        foreach ($variables as $variable) {
            $this->assertInstanceOf(Variable::class, $variable);
            $this->assertTrue(is_string($variable->getName()));
            $this->assertTrue(is_string($variable->getTypeAsString()));
        }
    }

    abstract protected function createCollector(): VariableCollectorInterface;

    abstract protected function variablesCount(): int;
}
