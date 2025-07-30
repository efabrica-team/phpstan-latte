<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Template;

use Efabrica\PHPStanLatte\Template\Form\Form;
use JsonSerializable;
use PHPStan\PhpDoc\TypeStringResolver;
use ReturnTypeWillChange;

final class Template implements JsonSerializable
{
    private string $path;

    /** @var ?class-string */
    private ?string $actualClass;

    private ?string $actualAction;

    private TemplateContext $templateContext;

    /** @var array<string> */
    private array $parentTemplatePaths;

    /**
     * @param ?class-string $actualClass
     * @param array<string> $parentTemplatePaths
     */
    public function __construct(
        string $path,
        ?string $actualClass,
        ?string $actualAction,
        TemplateContext $templateContext,
        array $parentTemplatePaths = []
    ) {
        $this->path = $path;
        $this->actualClass = $actualClass;
        $this->actualAction = $actualAction;
        $this->templateContext = $templateContext;
        $this->parentTemplatePaths = $parentTemplatePaths;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * @return ?class-string
     */
    public function getActualClass(): ?string
    {
        return $this->actualClass;
    }

    public function getActualAction(): ?string
    {
        return $this->actualAction;
    }

    public function getTemplateContext(): TemplateContext
    {
        return $this->templateContext;
    }

    /**
     * @return Variable[]
     */
    public function getVariables(): array
    {
        return $this->templateContext->getVariables();
    }

    /**
     * @return Component[]
     */
    public function getComponents(): array
    {
        return $this->templateContext->getComponents();
    }

    /**
     * @return Form[]
     */
    public function getForms(): array
    {
        return $this->templateContext->getForms();
    }

    /**
     * @return Filter[]
     */
    public function getFilters(): array
    {
        return $this->templateContext->getFilters();
    }

    /**
     * @return array<string>
     */
    public function getParentTemplatePaths(): array
    {
        return $this->parentTemplatePaths;
    }

    public function getSignatureHash(): string
    {
        return md5((string)json_encode($this));
    }

    #[ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return [
            'path' => $this->path,
            'actualClass' => $this->actualClass,
            'actualAction' => $this->actualAction,
            'templateContext' => $this->templateContext->jsonSerialize(),
            'parentTemplatePaths' => $this->parentTemplatePaths,
        ];
    }

    public static function fromJson(array $data, TypeStringResolver $typeStringResolver): self
    {
        return new self(
            $data['path'],
            $data['actualClass'] ?? null,
            $data['actualAction'] ?? null,
            TemplateContext::fromJson($data['templateContext'], $typeStringResolver),
            $data['parentTemplatePaths'] ?? []
        );
    }
}
