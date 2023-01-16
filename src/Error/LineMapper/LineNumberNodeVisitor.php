<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Error\LineMapper;

use PhpParser\Comment\Doc;
use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

final class LineNumberNodeVisitor extends NodeVisitorAbstract
{
    private LineMap $lineMap;

    public function __construct(LineMap $lineMap)
    {
        $this->lineMap = $lineMap;
    }

    public function enterNode(Node $node): ?Node
    {
        $comments = $node->getComments();
        $docComment = $node->getDocComment();
        if ($docComment instanceof Doc) {
            $comments[] = $docComment;
        }

        foreach ($comments as $comment) {
            $commentText = $comment->getText();

            $pattern = '/\*(.*?)line (?<number>\d+)(.*?)\*/';
            preg_match($pattern, $commentText, $matches);

            $latteLine = isset($matches['number']) ? (int)$matches['number'] : null;
            if ($latteLine === null) {
                continue;
            }

            $this->lineMap->add($node->getStartLine(), $latteLine);
        }

        return null;
    }
}
