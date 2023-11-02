<?php
namespace App\Shift\TypeDetector;
class TypeDetector
{
    public function methodReturnType(string $class, string $method): ?string
    {
        $file = file_get_contents($this->classMap()[$class]);
        preg_match('/function\s+' . preg_quote($method) . '\s*\(.*\)\s*:\s*(\w+)/', $file, $matches);
        $returnType = $matches[1]??$this->methodReturnFromDocs($file, $method);
        if (in_array($returnType, ['string', 'int', 'array', 'bool', 'float', 'callable', 'void'])){
            return $returnType;
        }
        if($returnType === 'self' || $returnType === '$this'){
            return $class;
        }
        if (strpos($returnType, '\\') !== false) {
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

    private function methodReturnFromDocs(string $file, string $method): string{
        if(preg_match('#^\h*/\*\*(?:\R\h*\*.*)*\R\h*\*/\R(?=.*\bfunction command\b)#m', $file, $matches) === 1){
            preg_match('/@return\s+?(.+)\n/m', $matches[0], $matches);
        }
        if(isset($matches[1]) && $matches[1][0] === '\\'){
            $matches[1] = mb_substr($matches[1], 1);
        }
        return $matches[1]??'void';
    }

}
