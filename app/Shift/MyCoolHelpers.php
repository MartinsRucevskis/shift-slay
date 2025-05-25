<?php

namespace App\Shift;

use PhpParser\Node;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\EncapsedStringPart;
use PhpParser\PrettyPrinter\Standard;

trait MyCoolHelpers
{
    public function nodeToString(Node $node): string
    {
        $printer = new Standard();
        if ($node instanceof EncapsedStringPart) {
            return $node->value;
        }
        if ($node instanceof Identifier || $node instanceof Name) {
            return $node->toString();
        }
        if ($node instanceof Variable) {
            return is_string($node->name) ? '$' . $node->name : $printer->prettyPrint([$node->name]);
        }
        return $printer->prettyPrint([$node]);
    }
}
