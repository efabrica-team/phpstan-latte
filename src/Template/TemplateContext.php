<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Template;

use Efabrica\PHPStanLatte\Template\Form\Form;
use JsonSerializable;
use PHPStan\PhpDoc\TypeStringResolver;
use ReturnTypeWillChange;

final class TemplateContext implements JsonSerializable
{
    /** @var Variable[] */
    private array $variables;

    /** @var Component[] */
    private array $components;

    /** @var Form[] */
    private array $forms;

    /** @var Filter[] */
    private array $filters;

    /**
     * @param Variable[] $variables
     * @param Component[] $components
     * @param Form[] $forms
     * @param Filter[] $filters
     */
    public function __construct(
        array $variables = [],
        array $components = [],
        array $forms = [],
        array $filters = []
    ) {
        $this->variables = $variables;
        $this->components = $components;
        $this->forms = $forms;
        $this->filters = $filters;
    }

    /**
     * @return Variable[]
     */
    public function getVariables(): array
    {
        return $this->variables;
    }

    /**
     * @return Component[]
     */
    public function getComponents(): array
    {
        return $this->components;
    }

    /**
     * @return Form[]
     */
    public function getForms(): array
    {
        return $this->forms;
    }

    /**
     * @return Filter[]
     */
    public function getFilters(): array
    {
        return $this->filters;
    }

    /**
     * @param Variable[] $variables
     */
    public function withVariables(array $variables): self
    {
        $new = clone $this;
        $new->variables = $variables;
        return $new;
    }

    /**
     * @param Variable[] $variables
     */
    public function mergeVariables(array $variables): self
    {
        $new = clone $this;
        $new->variables = ItemCombinator::merge($this->variables, $variables);
        return $new;
    }

    /**
     * @param Component[] $components
     */
    public function withComponents(array $components): self
    {
        $new = clone $this;
        $new->components = $components;
        return $new;
    }

    /**
     * @param Component[] $components
     */
    public function mergeComponents(array $components): self
    {
        $new = clone $this;
        $new->components = ItemCombinator::merge($this->components, $components);
        return $new;
    }

    /**
     * @param Form[] $forms
     */
    public function withForms(array $forms): self
    {
        $new = clone $this;
        $new->forms = $forms;
        return $new;
    }

    /**
     * @param Form[] $forms
     */
    public function mergeForms(array $forms): self
    {
        $new = clone $this;
        $new->forms = ItemCombinator::merge($this->forms, $forms);
        return $new;
    }

    /**
     * @param Filter[] $filters
     */
    public function withFilters(array $filters): self
    {
        $new = clone $this;
        $new->filters = $filters;
        return $new;
    }

    /**
     * @param Filter[] $filters
     */
    public function mergeFilters(array $filters): self
    {
        $new = clone $this;
        $new->filters = ItemCombinator::merge($this->filters, $filters);
        return $new;
    }

    public function merge(TemplateContext $templateContext): self
    {
        $new = clone $this;
        $new->variables = ItemCombinator::merge($this->variables, $templateContext->variables);
        $new->components = ItemCombinator::merge($this->components, $templateContext->components);
        $new->forms = ItemCombinator::merge($this->forms, $templateContext->forms);
        $new->filters = ItemCombinator::merge($this->filters, $templateContext->filters);
        return $new;
    }

    public function union(TemplateContext $templateContext): self
    {
        $new = clone $this;
        $new->variables = ItemCombinator::union($this->variables, $templateContext->variables);
        $new->components = ItemCombinator::union($this->components, $templateContext->components);
        $new->forms = ItemCombinator::merge($this->forms, $templateContext->forms);
        $new->filters = ItemCombinator::merge($this->filters, $templateContext->filters);
        return $new;
    }

    public function getSignatureHash(): string
    {
        return md5((string)json_encode([
            'variables' => array_map(fn(Variable $variable) => $variable->getSignatureHash(), $this->variables),
            'components' => array_map(fn(Component $component) => $component->getSignatureHash(), $this->components),
            'forms' => array_map(fn(Form $form) => $form->getSignatureHash(), $this->forms),
            'filters' => array_map(fn(Filter $filter) => $filter->getSignatureHash(), $this->filters),
        ]));
    }

    #[ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return [
            'variables' => array_map(fn(Variable $variable) => $variable->jsonSerialize(), $this->variables),
            'components' => array_map(fn(Component $component) => $component->jsonSerialize(), $this->components),
            'forms' => array_map(fn(Form $form) => $form->jsonSerialize(), $this->forms),
            'filters' => array_map(fn(Filter $filter) => $filter->jsonSerialize(), $this->filters),
        ];
    }

    /**
     * @param array{variables: array<array<string, mixed>>, components: array<array<string, mixed>>, forms: array<array<string, mixed>>, filters: array<array<string, mixed>>} $data
     */
    public static function fromJson(array $data, TypeStringResolver $typeStringResolver): self
    {
        foreach ($data['variables'] as $key => $variableData) {
            $variables[$key] = Variable::fromJson($variableData, $typeStringResolver);
        }
        foreach ($data['components'] as $key => $componentData) {
            $components[$key] = Component::fromJson($componentData, $typeStringResolver);
        }
        foreach ($data['forms'] as $key => $formData) {
            $forms[$key] = Form::fromJson($formData, $typeStringResolver);
        }
        foreach ($data['filters'] as $key => $filterData) {
            $filters[$key] = Filter::fromJson($filterData, $typeStringResolver);
        }

        return new self(
            $variables ?? [],
            $components ?? [],
            $forms ?? [],
            $filters ?? []
        );
    }
}
