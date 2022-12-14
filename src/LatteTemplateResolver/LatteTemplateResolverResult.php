<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\LatteTemplateResolver;

use Efabrica\PHPStanLatte\Helper\ComponentsHelper;
use Efabrica\PHPStanLatte\Helper\VariablesHelper;
use Efabrica\PHPStanLatte\LatteContext\CollectedData\CollectedTemplateRender;
use Efabrica\PHPStanLatte\Template\Component;
use Efabrica\PHPStanLatte\Template\Filter;
use Efabrica\PHPStanLatte\Template\Form\Form;
use Efabrica\PHPStanLatte\Template\Template;
use Efabrica\PHPStanLatte\Template\Variable;
use InvalidArgumentException;
use PHPStan\Rules\RuleError;
use PHPStan\Rules\RuleErrorBuilder;
use function is_string;

final class LatteTemplateResolverResult
{
  /** @var array<string, Template>  */
    private array $templates = [];

  /** @var RuleError[]  */
    private array $errors = [];

  /**
   * @param Template[] $templates
   * @param RuleError[] $errors
   */
    public function __construct(array $templates = [], array $errors = [])
    {
        foreach ($templates as $template) {
            $this->addTemplate($template);
        }
        $this->errors = $errors;
    }

  /**
   * @return array<string, Template>
   */
    public function getTemplates(): array
    {
        return $this->templates;
    }

  /**
   * @return RuleError[]
   */
    public function getErrors(): array
    {
        return $this->errors;
    }

    public function addTemplate(Template $template): void
    {
        $this->templates[$template->getSignatureHash()] = $template;
    }

    public function addError(RuleError $error): void
    {
        $this->errors[] = $error;
    }

    public function addErrorFromBuilder(RuleErrorBuilder $error): void
    {
        $this->errors[] = $error->build();
    }

    /**
     * @param CollectedTemplateRender[] $templateRenders
     * @param Variable[] $variables
     * @param Component[] $components
     * @param Form[] $forms
     * @param Filter[] $filters
     * @param class-string $className
     */
    public function addTemplatesFromRenders(array $templateRenders, array $variables, array $components, array $forms, array $filters, string $className, string $action): void
    {
        foreach ($templateRenders as $templateRender) {
            $templatePath = $templateRender->getTemplatePath();
            if ($templatePath === false) {
                $this->addErrorFromBuilder(RuleErrorBuilder::message('Cannot automatically resolve latte template from expression.')
                    ->file($templateRender->getFile())
                    ->line($templateRender->getLine()));
            } elseif ($templatePath === null) {
                $this->addErrorFromBuilder(RuleErrorBuilder::message("Latte template was not set for $className::$action")
                    ->file($templateRender->getFile())
                    ->line($templateRender->getLine()));
            } else {
                $this->addTemplateFromRender($templateRender, $variables, $components, $forms, $filters, $className, $action);
            }
        }
    }

    /**
     * @param Variable[] $variables
     * @param Component[] $components
     * @param Form[] $forms
     * @param Filter[] $filters
     * @param class-string $className
     */
    public function addTemplateFromRender(CollectedTemplateRender $templateRender, array $variables, array $components, array $forms, array $filters, string $className, string $action): void
    {
        $templatePath = $templateRender->getTemplatePath();
        if (!is_string($templatePath)) {
            throw new InvalidArgumentException('Cannot add template from CollectedTemplate render without resolved template.');
        }

        $this->addTemplate(new Template(
            $templatePath,
            $className,
            $action,
            VariablesHelper::merge($variables, $templateRender->getVariables()),
            ComponentsHelper::merge($components, $templateRender->getComponents()),
            $forms,
            $filters
        ));
    }
}
