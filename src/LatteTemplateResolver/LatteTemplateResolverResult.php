<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\LatteTemplateResolver;

use Efabrica\PHPStanLatte\Template\Template;
use PHPStan\Rules\RuleError;
use PHPStan\Rules\RuleErrorBuilder;

final class LatteTemplateResolverResult
{
  /** @var Template[]  */
    private array $templates;

  /** @var RuleError[]  */
    private array $errors;

  /**
   * @param Template[] $templates
   * @param RuleError[] $errors
   */
    public function __construct(array $templates = [], array $errors = [])
    {
        $this->templates = $templates;
        $this->errors = $errors;
    }

  /**
   * @return Template[]
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
        $this->templates[] = $template;
    }

    public function addError(RuleError $error): void
    {
        $this->errors[] = $error;
    }

    public function addErrorFromBuilder(RuleErrorBuilder $error): void
    {
        $this->errors[] = $error->build();
    }
}
