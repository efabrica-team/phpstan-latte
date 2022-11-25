<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Rule;

use Efabrica\PHPStanLatte\Error\Error;
use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\String_;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleError;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements Rule<FuncCall>
 */
class LatteCompileErrorsRule implements Rule
{
    public function getNodeType(): string
    {
        return FuncCall::class;
    }

    /**
     * @param FuncCall $node
     * @return RuleError[]
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if (!$node->name instanceof Name) {
            return [];
        }

        if ($node->name->toLowerString() !== strtolower(Error::LATTE_COMPILE_ERROR)) {
            return [];
        }

        $message = 'Unknown latte compile error.';
        if (count($node->getArgs()) >= 1 && $node->getArgs()[0]->value instanceof String_) {
            $message = $node->getArgs()[0]->value->value;
        }

        $error = RuleErrorBuilder::message($message);

        if (count($node->getArgs()) >= 2 && $node->getArgs()[1]->value instanceof String_) {
            $error->tip($node->getArgs()[1]->value->value);
        }

        return [$error->build()];
    }
}
