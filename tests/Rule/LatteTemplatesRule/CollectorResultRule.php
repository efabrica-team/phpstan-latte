<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Tests\Rule\LatteTemplatesRule;

use Efabrica\PHPStanLatte\Collector\Finder\ResolvedNodeFinder;
use Efabrica\PHPStanLatte\LatteTemplateResolver\LatteTemplateResolverInterface;
use Efabrica\PHPStanLatte\Template\Component;
use Efabrica\PHPStanLatte\Template\Variable;
use Nette\Utils\Strings;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\CollectedDataNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleError;
use PHPStan\Rules\RuleErrorBuilder;

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
                $errors[] = RuleErrorBuilder::message("NODE $resolver " . $this->dumpValue($collectedResolvedNode->getParams()))->build();
                $templates = $latteTemplateResolver->resolve($collectedResolvedNode, $collectedDataNode)->getTemplates();
                foreach ($templates as $template) {
                    if ($template instanceof RuleError) {
                        continue;
                    }
                    $path = pathinfo($template->getPath(), PATHINFO_BASENAME);
                    $actualClass = $this->shortClassName($template->getActualClass());
                    $actionAction = $template->getActualAction();
                    $variables = array_values(array_unique(array_map(function (Variable $v) {
                        return $v->getName();
                    }, $template->getVariables())));
                    $components = array_values(array_unique(array_map(function (Component $v) {
                        return $v->getName();
                    }, $template->getComponents())));
                    $errors[] = RuleErrorBuilder::message(
                        "TEMPLATE $path $actualClass::$actionAction " .
                        $this->dumpValue($variables) . ' ' .
                        $this->dumpValue($components)
                    )->build();
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
