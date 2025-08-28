<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\LatteContext;

use Efabrica\PHPStanLatte\Analyser\LatteContextData;
use Efabrica\PHPStanLatte\PhpDoc\LattePhpDocResolver;
use Efabrica\PHPStanLatte\Resolver\ValueResolver\PathResolver;
use PHPStan\Reflection\ReflectionProvider;

final class LatteContextFactory
{
    private ReflectionProvider $reflectionProvider;

    private PathResolver $pathResolver;

    private LattePhpDocResolver $lattePhpDocResolver;

    public function __construct(ReflectionProvider $reflectionProvider, PathResolver $pathResolver, LattePhpDocResolver $lattePhpDocResolver)
    {
        $this->reflectionProvider = $reflectionProvider;
        $this->pathResolver = $pathResolver;
        $this->lattePhpDocResolver = $lattePhpDocResolver;
    }

    public function create(LatteContextData $latteContextData): LatteContext
    {
        return new LatteContext($latteContextData, $this->reflectionProvider, $this->pathResolver, $this->lattePhpDocResolver);
    }
}
