<?php

namespace App\Shift\TypeDetector;

use App\Shift\Objects\FileClass;

class TypeDetector
{
    private array $primitiveTypes = ['string', 'int', 'array', 'bool', 'float', 'callable', 'void'];
    private array $selfReferringTypes = ['self', '$this', 'static'];
    public function methodReturnType(string $class, string $method): ?string
    {
        $file = file_get_contents($this->classMap()[$class]);
        $methodObject = array_filter((new FileClass($this->classMap()[$class]))->availableMethods(true), function ($classMethod) use ($method) {
            return $classMethod->name === $method;
        });
        $returnType = array_pop($methodObject)->returnType;

        if ($this->isSelfReferringType($returnType)) {
            return $class;
        }

        if ($this->isPrimitiveType($returnType) || $this->isNamespacedType($returnType)) {
            return $returnType;
        }
        preg_match_all('/use\s+(.*);/', $file, $matches);
        $uses = $matches[1];

        foreach ($uses as $use) {
            if (substr($use, strrpos($use, '\\') + 1) === $returnType) {
                return $use;
            }
            if (strpos($use, ' as ') !== false) {
                list($use, $alias) = explode(' as ', $use);
                if ($alias === $returnType) {
                    return $use;
                }
            }
        }
        preg_match('/namespace\s+(.*);/', $file, $matches);
        $namespace = $matches[1];
        return "$namespace\\$returnType";
    }

    private function classMap(): array
    {
        return require config('shift.composer_path');
    }

    private function isNamespacedType(string $type): bool
    {
        return str_contains($type, '\\');
    }

    private function isPrimitiveType(string $type): bool
    {
        return in_array($type, $this->primitiveTypes);
    }

    private function isSelfReferringType(string $type): bool
    {
        return in_array($type, $this->selfReferringTypes);
    }

//    private function methodReturnFromDocs(string $file, string $method): string
//    {
//        if (preg_match('#^\h*/\*\*(?:\R\h*\*.*)*\R\h*\*/\R(?=.*\bfunction ' . preg_quote($method) . '\b)#m', $file, $matches) === 1) {
//            preg_match('/@return\s+?(.+)\n/m', $matches[0], $matches);
//        }
//        if (isset($matches[1]) && $matches[1][0] === '\\') {
//            $matches[1] = mb_substr($matches[1], 1);
//        }
//        return $matches[1] ?? 'void';
//    }

}
