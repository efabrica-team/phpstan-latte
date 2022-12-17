<?php

namespace Efabrica\PHPStanLatte\Collector;

use Efabrica\PHPStanLatte\Collector\ValueObject\CollectedValueObject;
use Efabrica\PHPStanLatte\Resolver\NameResolver\NameResolver;
use Efabrica\PHPStanLatte\Type\TypeSerializer;
use PhpParser\Node;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\TraitUse;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Analyser\Scope;
use PHPStan\Collectors\CollectedData;
use PHPStan\Collectors\Collector;
use PHPStan\Node\CollectedDataNode;
use PHPStan\Parser\Parser;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\ShouldNotHappenException;

/**
 * @template N of Node
 * @template T of CollectedValueObject
 * @template A of array
 * @implements Collector<N, ?A[]>
 */
abstract class AbstractCollector implements Collector
{
    protected TypeSerializer $typeSerializer;

    protected NameResolver $nameResolver;

    protected ReflectionProvider $reflectionProvider;

    protected Parser $parser;

    protected NodeScopeResolver $nodeScopeResolver;

    public function __construct(
        TypeSerializer $typeSerializer,
        NameResolver $nameResolver,
        ReflectionProvider $reflectionProvider,
        Parser $parser,
        NodeScopeResolver $nodeScopeResolver
    ) {
        $this->typeSerializer = $typeSerializer;
        $this->nameResolver = $nameResolver;
        $this->reflectionProvider = $reflectionProvider;
        $this->parser = $parser;
        $this->nodeScopeResolver = $nodeScopeResolver;
    }

    /**
     * @param class-string $class
     * @return T[]
     */
    public static function loadData(CollectedDataNode $collectedDataNode, TypeSerializer $typeSerializer, string $class)
    {
        $data = array_filter(array_merge(...array_values($collectedDataNode->get(static::class))));
        $collected = [];
        foreach ($data as $itemList) {
            foreach ($itemList as $item) {
                $collected[] = $class::fromArray($item, $typeSerializer);
            }
        }
        return $collected;
    }

  /**
   * @param array<CollectedData> $collectedDataList
   * @param class-string $class
   * @return T[]
   */
    public function extractCollectedData(array $collectedDataList, TypeSerializer $typeSerializer, string $class): array
    {
        $collectedTemplateRenders = [];
        foreach ($collectedDataList as $collectedData) {
            if ($collectedData->getCollectorType() !== static::class) {
                continue;
            }
            /** @phpstan-var A[] $dataList */
            $dataList = $collectedData->getData();
            foreach ($dataList as $data) {
                $collectedTemplateRenders[] = $class::fromArray($data, $typeSerializer);
            }
        }
        return $collectedTemplateRenders;
    }

    /**
     * @phpstan-param array<T> $items
     * @return ?A[]
     */
    protected function collectItems(array $items): ?array
    {
        if (count($items) === 0) {
            return null;
        }
        $data = [];
        foreach ($items as $item) {
            $data[] = $item->toArray($this->typeSerializer);
        }
        return $data;
    }

    /**
     * @phpstan-param T $item
     * @return A[]
     */
    protected function collectItem(CollectedValueObject $item): array
    {
        return [$item->toArray($this->typeSerializer)];
    }

    /**
     * @phpstan-return null|A[]
     */
    public function processNode(Node $node, Scope $scope): ?array
    {
        if ($node instanceof TraitUse) {
            $traitItems = [];
            foreach ($node->traits as $trait) {
                $traitName = $this->nameResolver->resolve($trait);
                if ($traitName === null) {
                    continue;
                }

                $traitClassReflection = $this->reflectionProvider->getClass($traitName);
                $traitFileName = $traitClassReflection->getFileName();
                if ($traitFileName === null) {
                    continue;
                }

                $scope = $scope->enterTrait($traitClassReflection);

                $traitNodes = $this->parser->parseFile($traitFileName);
                $traitItems = array_merge($traitItems, $this->processTraitUseNodes($traitNodes, $scope));
            }
            return $traitItems;
        }
        return $this->collectData($node, $scope);
    }

    /**
     * @param Node|Node[] $node
     * @param MutatingScope $scope
     * @phpstan-return A[]
     * @throws ShouldNotHappenException
     */
    private function processTraitUseNodes($node, Scope $scope): array
    {
        $return = [];
        if ($node instanceof Node) {
            if ($node instanceof ClassMethod) {
                [$templateTypeMap, $phpDocParameterTypes, $phpDocReturnType, $phpDocThrowType, $deprecatedDescription, $isDeprecated, $isInternal, $isFinal, $isPure, $acceptsNamedArguments, , $phpDocComment, $asserts, $selfOutType, $phpDocParameterOutTypes] = $this->nodeScopeResolver->getPhpDocs($scope, $node);
                $scope = $scope->enterClassMethod($node, $templateTypeMap, $phpDocParameterTypes, $phpDocReturnType, $phpDocThrowType, $deprecatedDescription, $isDeprecated, $isInternal, $isFinal, $isPure, $acceptsNamedArguments, $asserts, $selfOutType, $phpDocComment, $phpDocParameterOutTypes);
            }
            foreach ($node->getSubNodeNames() as $subNodeName) {
                $return = array_merge($return, $this->processTraitUseNodes($node->{$subNodeName}, $scope));
            }
            /** @var N $node */
            $procesNodeReturn = $this->processNode($node, $scope);
            if ($procesNodeReturn !== null) {
                $return = array_merge($procesNodeReturn);
            }
        } else {
            foreach ($node as $oneNode) {
                $return = array_merge($return, $this->processTraitUseNodes($oneNode, $scope));
            }
        }

        return $return;
    }

    /**
     * @phpstan-return null|A[]
     */
    abstract protected function collectData(Node $node, Scope $scope): ?array;
}
