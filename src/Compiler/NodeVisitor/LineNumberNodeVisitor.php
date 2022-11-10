<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Compiler\NodeVisitor;

use Efabrica\PHPStanLatte\Compiler\LineMapper;
use PhpParser\Comment\Doc;
use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

final class LineNumberNodeVisitor extends NodeVisitorAbstract
{
    private LineMapper $lineMapper;

    public function __construct(LineMapper $lineMapper)
    {
        $this->lineMapper = $lineMapper;
    }

    public function enterNode(Node $node): ?Node
    {
        $comments = $node->getComments();
        $docComment = $node->getDocComment();
        if ($docComment instanceof Doc) {
            $comments[] = $docComment;
        }

        // TODO check if lines are correct, it seems if, foreach etc. has comment inside of them
        foreach ($comments as $comment) {
            $commentText = $comment->getText();

            $pattern = '/\*(.*?)line (?<number>\d+)(.*?)\*/';
            preg_match($pattern, $commentText, $matches);

            $latteLine = isset($matches['number']) ? (int)$matches['number'] : null;
            if ($latteLine === null) {
                continue;
            }

            $this->lineMapper->add($node->getStartLine(), $latteLine);
        }

        return null;
    }
}
