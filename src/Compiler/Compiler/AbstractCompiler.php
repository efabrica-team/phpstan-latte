<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Compiler\Compiler;

use Latte\Engine;
use PHPStan\Reflection\Native\NativeParameterReflection;
use PHPStan\Reflection\PassedByReference;
use PHPStan\Type\ArrayType;
use PHPStan\Type\CallableType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\VerbosityLevel;
use PHPStan\Type\VoidType;

abstract class AbstractCompiler implements CompilerInterface
{
    protected Engine $engine;

    protected bool $strictMode;

    /** @var array<string, string|array{string, string}> */
    protected array $filters;

    /** @var array<string, string|array{string, string}> */
    protected array $functions;

    /**
     * @param array<string, string|array{string, string}> $filters
     * @param array<string, string|array{string, string}> $functions
     */
    public function __construct(?Engine $engine = null, bool $strictMode = false, array $filters = [], array $functions = [])
    {
        if ($engine === null) {
            $engine = $this->createDefaultEngine();
        }
        $this->engine = $engine;
        $this->strictMode = $strictMode;
        $this->filters = $filters;
        $this->functions = $functions;
        $this->installFunctions($functions);
    }

    public function generateClassName(): string
    {
        return 'PHPStanLatteTemplate_' . md5(uniqid());
    }

    public function generateClassComment(string $className, string $context): string
    {
        $comment = "\n * $context\n";
        $comment .= " * @property {$className}_global \$global\n";
        $comment .= ' * @generated ' . date('Y-m-d H:i:s') . "\n";
        $comment .= "\n";
        return $comment;
    }

    /**
     * @param array<string, mixed> $types
     */
    protected function generateTypes(string $className, array $types): string
    {
        $phpCode = "/**\n";
        foreach ($types as $name => $value) {
            if ($value instanceof Type) {
                $type = $value->describe(VerbosityLevel::precise());
            } else {
                $type = is_object($value) ? get_class($value) : gettype($value);
            }
            $phpCode .= " * @property {$type} \${$name}\n";
        }
        $phpCode .= " */\n";
        $phpCode .= "class {$className} extends \\stdClass { }\n";
        return $phpCode;
    }

    protected function addTypes(string $phpContent, string $className, ?string $actualClass): string
    {
        /** @var array<string, mixed> $providers */
        $providers = $this->engine->getProviders();
        $providers['uiControl'] = new ObjectType($actualClass ?? 'Nette\Application\UI\Control');
        $providers['uiPresenter'] = new ObjectType($actualClass ?? 'Nette\Application\UI\Presenter');
        $providers['snippetDriver'] = new ObjectType(class_exists('Nette\Bridges\ApplicationLatte\SnippetDriver') ? 'Nette\Bridges\ApplicationLatte\SnippetDriver' : 'Nette\Bridges\ApplicationLatte\SnippetRuntime');
        $providers['uiNonce'] = TypeCombinator::addNull(new StringType());
        $providers['formsStack'] = new ArrayType(new IntegerType(), new ObjectType('Nette\Forms\Container'));

        $coreExceptionHandlerParameters = [
            new NativeParameterReflection('exception', false, new ObjectType('\Throwable'), PassedByReference::createNo(), false, null),
            new NativeParameterReflection('template', false, new ObjectType('\Latte\Runtime\Template'), PassedByReference::createNo(), false, null),
        ];
        $providers['coreExceptionHandler'] = new CallableType($coreExceptionHandlerParameters, new VoidType());
        $phpContent .= $this->generateTypes($className . '_global', $providers);
        return $phpContent;
    }

    /**
     * @param array<string, string|array{string, string}> $functions
     */
    private function installFunctions(array $functions): void
    {
        foreach ($functions as $name => $function) {
            if (is_callable($function)) {
                $this->engine->addFunction($name, $function);
            } else {
                // add placeholder function
                $this->engine->addFunction($name, function (...$args) {
                    return null;
                });
            }
        }
    }

    abstract protected function createDefaultEngine(): Engine;
}
