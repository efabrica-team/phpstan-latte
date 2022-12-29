<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\PhpDoc;

use Nette\Utils\Strings;
use PhpParser\Node;
use PHPStan\Analyser\NameScope;
use PHPStan\Analyser\Scope;
use PHPStan\PhpDoc\TypeNodeResolver;
use PHPStan\PhpDocParser\Ast\PhpDoc\GenericTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocNode;
use PHPStan\PhpDocParser\Lexer\Lexer;
use PHPStan\PhpDocParser\Parser\TokenIterator;
use PHPStan\PhpDocParser\Parser\TypeParser;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\MissingMethodFromReflectionException;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\FileTypeMapper;
use PHPStan\Type\Type;

final class LattePhpDocResolver
{
    private Lexer $phpDocLexer;

    private TypeParser $typeParser;

    private TypeNodeResolver $typeNodeResolver;

    private FileTypeMapper $fileTypeMapper;

    private ReflectionProvider $reflectionProvider;

    // TODO: Implement caching to prevent repeated parsing of same doc comment
    public function __construct(Lexer $phpDocLexer, TypeParser $typeParser, TypeNodeResolver $typeNodeResolver, FileTypeMapper $fileTypeMapper, ReflectionProvider $reflectionProvider)
    {
        $this->phpDocLexer = $phpDocLexer;
        $this->typeParser = $typeParser;
        $this->typeNodeResolver = $typeNodeResolver;
        $this->fileTypeMapper = $fileTypeMapper;
        $this->reflectionProvider = $reflectionProvider;
    }

    public function resolve(?string $commentText, ClassReflection $classReflection): LattePhpDoc
    {
        if ($commentText === null || $commentText === '') {
            return new LattePhpDoc();
        }

        $resolvedPhpDoc = $this->fileTypeMapper->getResolvedPhpDoc(
            $classReflection->getFileName(),
            $classReflection->getName(),
            null,
            null,
            $commentText,
        );

        $nameScope = $resolvedPhpDoc->getNullableNameScope() ??
            new NameScope(null, [], $classReflection->getName());

        $isIgnored = false;
        $templatePaths = [];
        $variables = [];
        $components = [];
        foreach ($resolvedPhpDoc->getPhpDocNodes() as $phpDocNode) {
            $isIgnored = $isIgnored || count($phpDocNode->getTagsByName('@phpstan-latte-ignore')) > 0;
            $templatePaths = array_merge($templatePaths, $this->parseTemplateTags($phpDocNode, $classReflection));
            $variables = array_merge($variables, $this->parseVariableTags($phpDocNode, $nameScope));
            $components = array_merge($components, $this->parseComponentTags($phpDocNode, $nameScope));
        }

        return new LattePhpDoc($isIgnored, $templatePaths, $variables, $components);
    }

    /**
     * @return array<string>
     */
    private function parseTemplateTags(PhpDocNode $phpDocNode, ClassReflection $classReflection): array
    {
        $templatePaths = [];
        foreach ($phpDocNode->getTagsByName('@phpstan-latte-template') as $templateTag) {
            if (!$templateTag->value instanceof GenericTagValueNode) {
                continue;
            }
            $templatePaths[] = $this->replacePlaceholders($templateTag->value->value, $classReflection);
        }
        return $templatePaths;
    }

    /**
     * @return array<string, Type>
     */
    private function parseVariableTags(PhpDocNode $phpDocNode, NameScope $nameScope): array
    {
        $variables = [];
        foreach ($phpDocNode->getTagsByName('@phpstan-latte-var') as $variableTag) {
            if (!$variableTag->value instanceof GenericTagValueNode) {
                continue;
            }
            $typeAndName = $this->parseTypeAndName($variableTag->value->value, $nameScope);
            $variables[$typeAndName['name'] ?? ''] = $typeAndName['type'];
        }
        return $variables;
    }

    /**
     * @return array<string, Type>
     */
    private function parseComponentTags(PhpDocNode $phpDocNode, NameScope $nameScope): array
    {
        $components = [];
        foreach ($phpDocNode->getTagsByName('@phpstan-latte-component') as $variableTag) {
            if (!$variableTag->value instanceof GenericTagValueNode) {
                continue;
            }
            $typeAndName = $this->parseTypeAndName($variableTag->value->value, $nameScope);
            $components[$typeAndName['name'] ?? ''] = $typeAndName['type'];
        }
        return $components;
    }

    public function resolveForNode(Node $node, Scope $scope): LattePhpDoc
    {
        if ($scope->getClassReflection() === null) {
            return new LattePhpDoc();
        }
        $docNode = $node->getDocComment();
        if ($docNode !== null) {
            $lattePhpDoc = $this->resolve($docNode->getText(), $scope->getClassReflection());
        } else {
            $lattePhpDoc = new LattePhpDoc();
        }

        if ($scope->getFunctionName() !== null) {
            $lattePhpDoc->setParentMethod($this->resolveForMethod($scope->getClassReflection()->getName(), $scope->getFunctionName()));
        } else {
            $lattePhpDoc->setParentClass($this->resolveForClass($scope->getClassReflection()->getName()));
        }
        return $lattePhpDoc;
    }

    public function resolveForMethod(string $className, string $methodName): LattePhpDoc
    {
        $classReflection = $this->reflectionProvider->getClass($className);
        try {
            $commentText = $classReflection->getNativeMethod($methodName)->getDocComment();
            $lattePhpDoc = $this->resolve($commentText, $classReflection);
        } catch (MissingMethodFromReflectionException $e) {
            $lattePhpDoc = new LattePhpDoc(); // probably virtual method added by @method annotation
        }
        $lattePhpDoc->setParentClass($this->resolveForClass($className));
        return $lattePhpDoc;
    }

    public function resolveForClass(string $className): LattePhpDoc
    {
        $classReflection = $this->reflectionProvider->getClass($className);
        $commentText = $classReflection->getNativeReflection()->getDocComment() ?: null;
        return $this->resolve($commentText, $classReflection);
    }

    /**
     * @return array{type: Type, name: ?string}
     */
    private function parseTypeAndName(string $valueString, NameScope $nameScope): array
    {
        $tokens = new TokenIterator($this->phpDocLexer->tokenize($valueString));
        $typeNode = $this->typeParser->parse($tokens);
        $type = $this->typeNodeResolver->resolve($typeNode, $nameScope);
        $name = $this->parseOptionalVariableName($tokens);
        return ['type' => $type, 'name' => $name];
    }

    private function parseOptionalVariableName(TokenIterator $tokens): string
    {
        if ($tokens->isCurrentTokenType(Lexer::TOKEN_VARIABLE)) {
            $parameterName = $tokens->currentTokenValue();
            $tokens->next();
            if ($parameterName[0] === '$') {
                $parameterName = substr($parameterName, 1);
            }
            return $parameterName;
        } elseif ($tokens->isCurrentTokenType(Lexer::TOKEN_THIS_VARIABLE)) {
            $tokens->next();
            return '';
        } else {
            return '';
        }
    }

    private function replacePlaceholders(string $value, ClassReflection $classReflection): string
    {
        $value = str_replace('{file}', $classReflection->getFileName() ?? '{file}', $value);
        $value = str_replace('{dir}', dirname($classReflection->getFileName() ?? '{dir}'), $value);
        $value = str_replace('{baseName}', pathinfo($classReflection->getFileName() ?? '{baseName}', PATHINFO_BASENAME), $value);
        $value = str_replace('{fileName}', pathinfo($classReflection->getFileName() ?? '{fileName}', PATHINFO_FILENAME), $value);
        $value = str_replace('{className}', Strings::after($classReflection->getName(), '\\', -1) ?? '{className}', $value);
        return $value;
    }
}
