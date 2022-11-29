<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Tests\Rule\LatteTemplatesRule;

use Efabrica\PHPStanLatte\Collector\Finder\ResolvedNodeFinder;
use Efabrica\PHPStanLatte\LatteTemplateResolver\LatteTemplateResolverInterface;
use Efabrica\PHPStanLatte\Template\Component;
use Efabrica\PHPStanLatte\Template\Variable;
use Nette\Utils\Strings;
use PhpParser\Node;
use PHPStan\Analyser\Error;
use PHPStan\Analyser\Scope;
use PHPStan\Node\CollectedDataNode;
use PHPStan\Rules\Rule;

/**
 * @implements Rule<CollectedDataNode>
 */
final class CollectorResultRule implements Rule
{
    /** @var LatteTemplateResolverInterface[] */
    private array $latteTemplateResolvers;

    /**
     * @param LatteTemplateResolverInterface[] $latteTemplateResolvers
     */
    public function __construct(array $latteTemplateResolvers)
    {
        $this->latteTemplateResolvers = $latteTemplateResolvers;
    }

    public function getNodeType(): string
    {
        return CollectedDataNode::class;
    }

    /**
     * @param CollectedDataNode $collectedDataNode
     */
    public function processNode(Node $collectedDataNode, Scope $scope): array
    {
        $errors = [];

        $resolvedNodeFinder = new ResolvedNodeFinder($collectedDataNode);
        foreach ($this->latteTemplateResolvers as $latteTemplateResolver) {
            foreach ($resolvedNodeFinder->find(get_class($latteTemplateResolver)) as $collectedResolvedNode) {
                $resolver = $this->shortClassName($collectedResolvedNode->getResolver());
                $errors[] = new Error(
                    "NODE $resolver " . $this->dumpValue($collectedResolvedNode->getParams()),
                    $scope->getFile()
                );
                $templates = $latteTemplateResolver->findTemplates($collectedResolvedNode, $collectedDataNode);
                foreach ($templates as $template) {
                    $path = pathinfo($template->getPath(), PATHINFO_BASENAME);
                    $presenter = $this->shortClassName($template->getActualClass());
                    $variables = array_map(function (Variable $v) {
                        return $v->getName();
                    }, $template->getVariables());
                    $components = array_map(function (Component $v) {
                        return $v->getName();
                    }, $template->getComponents());
                    $errors[] = new Error(
                        "TEMPLATE $path $presenter " .
                        $this->dumpValue($variables) . ' ' .
                        $this->dumpValue($components),
                        $template->getPath()
                    );
                }
            }
        }

        return $errors;
    }

    private function shortClassName(string $className): string
    {
        return Strings::after($className, '\\', -1);
    }

    /**
     * @param mixed $value
     */
    private function dumpValue($value): string
    {
        $value = json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $value = str_replace('\\\\', '\\', $value);
        $value = str_replace(__NAMESPACE__, '', $value);
        return $value;
    }
}
