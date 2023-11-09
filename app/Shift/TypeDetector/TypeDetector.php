<?php

declare(strict_types=1);

namespace App\Shift\TypeDetector;

use App\Shift\Objects\FileClass;
use Exception;

class TypeDetector
{
    /**
     * @var string[]
     */
    private array $primitiveTypes = ['string', 'int', 'array', 'bool', 'float', 'callable', 'void'];

    /**
     * @var string[]
     */
    private array $selfReferringTypes = ['self', '$this', 'static'];

    public function methodReturnType(?string $class, ?string $method): ?string
    {
        if (! isset($class) || ! isset($method)) {
            return null;
        }

        $file = file_get_contents($this->classMap()[$class]) ?: throw new Exception('Failed to read class: '.$class);
        $methodObject = array_filter((new FileClass($this->classMap()[$class]))->availableMethods(true), static fn ($classMethod): bool => $classMethod->name === $method);
        $methoda = array_pop($methodObject);
        $returnType = $methoda->returnType ?? 'void';

        if ($this->isSelfReferringType($returnType)) {
            return $class;
        }

        if ($this->isPrimitiveType($returnType) || $this->isNamespacedType($returnType)) {
            return $returnType;
        }

        preg_match_all('/use\s+(.*);/', $file, $matches);
        $uses = $matches[1];

        foreach ($uses as $use) {
            if (substr((string) $use, strrpos((string) $use, '\\') + 1) === $returnType) {
                return $use;
            }

            if (str_contains((string) $use, ' as ')) {
                [$use, $alias] = explode(' as ', (string) $use);
                if ($alias === $returnType) {
                    return $use;
                }
            }
        }

        preg_match('/namespace\s+(.*);/', $file, $matches);
        $namespace = $matches[1];

        return sprintf('%s\%s', $namespace, $returnType);
    }

    /**
     * @return array<string, string>
     */
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
