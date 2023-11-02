<?php

namespace App\Shift\Objects;

class ClassImports
{
    private string $alias;
    private bool $isVendor;

    public function __construct(string $className, $alias)
    {
        $matches = preg_split(' ', $use);
        $this->filePath = new \ReflectionClass(get_class($matches[1]));
        if ($matches[2] === 'as'){
            $this->nameInScope = $matches[3];
        } else{
            $className = preg_split('/', $matches[1]);
            $this->nameInScope = end($className);
        }
    }

}
