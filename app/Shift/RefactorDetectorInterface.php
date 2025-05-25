<?php

namespace App\Shift;

use PhpParser\Node;

interface RefactorDetectorInterface
{
    public function detect(string $className, string $methodName, Node $oldStmt, Node $newStmt): ?array;
}
