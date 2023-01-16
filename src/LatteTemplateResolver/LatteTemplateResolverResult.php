<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\LatteTemplateResolver;

use Efabrica\PHPStanLatte\LatteContext\CollectedData\CollectedTemplateRender;
use Efabrica\PHPStanLatte\Template\Template;
use Efabrica\PHPStanLatte\Template\TemplateContext;
use PHPStan\Rules\RuleError;
use PHPStan\Rules\RuleErrorBuilder;

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
     * @param class-string $className
     */
    public function addTemplateFromRender(CollectedTemplateRender $templateRender, TemplateContext $templateContext, string $className, string $action): void
    {
        $templatePath = $templateRender->getTemplatePath();
        if ($templatePath === false) {
            $this->addErrorFromBuilder(RuleErrorBuilder::message('Cannot automatically resolve latte template from expression.')
                ->file($templateRender->getFile())
                ->line($templateRender->getLine()));
            return;
        } elseif ($templatePath === null) {
            $this->addErrorFromBuilder(RuleErrorBuilder::message("Latte template was not set for $className::$action")
                ->file($templateRender->getFile())
                ->line($templateRender->getLine()));
            return;
        } elseif (!is_file($templatePath)) {
            $this->addErrorFromBuilder(RuleErrorBuilder::message('Rendered latte template ' . $templatePath . ' does not exist.')
                ->file($templateRender->getFile())
                ->line($templateRender->getLine()));
            return;
        }

        $this->addTemplate(new Template(
            $templatePath,
            $className,
            $action,
            $templateContext
                ->mergeVariables($templateRender->getVariables())
                ->mergeComponents($templateRender->getComponents())
        ));
    }
}
