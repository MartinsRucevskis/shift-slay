<?php

namespace App\Shift;

use PhpParser\Node;

trait ClassResolverTrait {

    use MyCoolHelpers;
    protected function resolveClass(Node $classNode): string {
        $resolverName = $classNode->getAttribute('resolvedName');
        if (empty($resolverName) && isset($classNode->parts)) {
            $resolverName = implode('\\', $classNode->parts);
            if ($resolverName){
                return $resolverName;
            }
        }
        if ($resolverName) {
            return $resolverName->toString();
        }
        $fallback = $this->nodeToString($classNode);

        return $fallback !== '' ? $fallback : '???';
    }
}
