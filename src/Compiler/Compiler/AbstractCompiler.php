<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Compiler\Compiler;

use Latte\Engine;
use PHPStan\Type\ArrayType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\VerbosityLevel;

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
    }

    public function generateClassName(): string
    {
        return 'PHPStanLatteTemplate_' . md5(uniqid());
    }

    public function generateClassComment(string $className, string $context): string
    {
        $comment = "\n* $context\n";
        $comment .= "* @property {$className}_global \$global\n";
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
            $phpCode .= "* @property {$type} \${$name}\n";
        }
        $phpCode .= "*/\n";
        $phpCode .= "class {$className} extends \\stdClass { }\n";
        return $phpCode;
    }

    protected function addTypes(string $phpContent, string $className, ?string $actualClass): string
    {
        $providers = $this->engine->getProviders();
        $providers['uiControl'] = new ObjectType($actualClass ?? 'Nette\Application\UI\Control');
        $providers['uiPresenter'] = new ObjectType($actualClass ?? 'Nette\Application\UI\Presenter');
        $providers['snippetDriver'] = TypeCombinator::addNull(new ObjectType('Nette\Bridges\ApplicationLatte\SnippetDriver'));
        $providers['uiNonce'] = TypeCombinator::addNull(new StringType());
        $providers['formsStack'] = new ArrayType(new IntegerType(), new ObjectType('Nette\Forms\Container'));
        $phpContent .= $this->generateTypes($className . '_global', $providers);
        return $phpContent;
    }

    abstract protected function createDefaultEngine(): Engine;
}
