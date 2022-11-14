<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Error;

final class Error
{
    private string $message;

    private ?string $tip;

    public function __construct(string $message, ?string $tip = null)
    {
        $this->message = $message;
        $this->tip = $tip;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function setMessage(string $message): Error
    {
        $this->message = $message;
        return $this;
    }

    public function getTip(): ?string
    {
        return $this->tip;
    }

    public function setTip(?string $tip): Error
    {
        $this->tip = $tip;
        return $this;
    }
}
