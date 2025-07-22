<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Compiler\NodeVisitor\Behavior;

use Efabrica\PHPStanLatte\Template\Component;
use PhpParser\ConstExprEvaluationException;
use PhpParser\ConstExprEvaluator;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\InterpolatedStringPart;
use PhpParser\Node\Scalar\Encapsed;

trait ComponentsNodeVisitorBehavior
{
    /** @var Component[] */
    private array $components = [];

    /**
     * @param Component[] $components
     */
    public function setComponents(array $components): void
    {
        $this->components = $components;
    }

    /**
     * @param Component[] $components
     */
    private function findComponentByName(array $components, string $componentName): ?Component
    {
        $componentNameParts = explode('-', $componentName);
        $componentNamePart = array_shift($componentNameParts);
        foreach ($components as $component) {
            if ($component->getName() !== $componentNamePart) {
                continue;
            }
            if (count($componentNameParts) === 0) {
                return $component;
            }
            return $this->findComponentByName($component->getSubcomponents() ?: [], implode('-', $componentNameParts));
        }
        foreach ($components as $component) {
            if ($component->getName() === '*') {
                return $component;
            }
        }
        return null;
    }

    /**
     * @return mixed
     */
    private function evaluateComponentName(Expr $expr)
    {
        $constExprEvaluator = new ConstExprEvaluator(function (Expr $expr) {
            if ($expr instanceof ConstFetch) {
                return constant((string)$expr->name);
            }

            if ($expr instanceof Variable) {
                return '*';
            }

            if ($expr instanceof Encapsed) {
                $result = [];
                foreach ($expr->parts as $part) {
                    if ($part instanceof InterpolatedStringPart) {
                        $result[] = $part->value;
                    } else {
                        $result[] = $this->evaluateComponentName($part);
                    }
                }
                return implode('', $result);
            }

            throw new ConstExprEvaluationException();
        });
        return $constExprEvaluator->evaluateDirectly($expr);
    }

    private function getComponentNameByExpr(Expr $expr): ?string
    {
        try {
            $componentName = $this->evaluateComponentName($expr);
            if (!is_string($componentName) || $componentName === '*') {
                return null;
            }
            return $componentName;
        } catch (ConstExprEvaluationException $e) {
            return null;
        }
    }
}
