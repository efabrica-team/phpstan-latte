<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Error;

use PhpParser\Node\Arg;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Expression;

final class Error
{
    public const LATTE_COMPILE_ERROR = '__latteCompileError';

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

    public function toNode(): Expression
    {
        $params = [new Arg(new String_($this->message))];
        if ($this->tip !== null) {
            $params[] = new Arg(new String_($this->tip));
        }
        return new Expression(
            new FuncCall(new Name(self::LATTE_COMPILE_ERROR), $params)
        );
    }
}
