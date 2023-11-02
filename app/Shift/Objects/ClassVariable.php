<?php

namespace App\Shift\Objects;

use App\Shift\Enums\VisibilityEnum;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Property;

class ClassVariable
{
    public ?string $type;
    public string $class;
    public string $name;
    public string $visibility;
    public string $className;

    public function __construct(Property $property, string $className, string $filecontents)
    {
        $this->className = $className;
        $this->name = $property->props[0]->name;
        $this->visibility = $this->visibility($property);
        $this->type = $property->type ?? $this->typeFromDocBlock($filecontents);
    }
    private function visibility(Property|ClassMethod $instance): string
    {
        $visibility = VisibilityEnum::PRIVATE;
        if ($instance->isPublic()) {
            $visibility = VisibilityEnum::PUBLIC;
        } elseif ($instance->isProtected()) {
            $visibility = VisibilityEnum::PROTECTED;
        }

        return $visibility;
    }

    private function typeFromDocBlock(string $fileContents): ?string {
        if(preg_match('#^\h*/\*\*(?:\R\h*\*.*)*\R\h*\*/\R(?=.*\b'.preg_quote($this->visibility).'\s+\$'.preg_quote($this->name).'\b)#m', $fileContents, $matches)) {
            preg_match('/@var\s+?(.+)\n/m', $matches[0], $matches);
        }
        if(isset($matches[1]) && $matches[1][0] === '\\'){
            $matches[1] = mb_substr($matches[1], 1);
        }
        return $matches[1];
    }

}
