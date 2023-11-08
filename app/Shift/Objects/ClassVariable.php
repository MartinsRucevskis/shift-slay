<?php

declare(strict_types=1);

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

    public function __construct(Property $property, public string $className, string $fileContents)
    {
        $this->name = $property->props[0]->name;
        $this->visibility = $this->visibility($property);
        $this->type = $property->type ?? $this->typeFromDocBlock($fileContents);
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

    private function typeFromDocBlock(string $fileContents): ?string
    {
        if (preg_match('#^\h*/\*\*(?:\R\h*\*.*)*\R\h*\*/\R(?=.*\b'.preg_quote($this->visibility).'\s+(static\s+)?\$'.preg_quote($this->name).'\b)#m', $fileContents, $docBlock)) {
            preg_match('/@var\s+?(.+)\n/m', $docBlock[0], $varType);
        }
        if (isset($varType[1]) && $varType[1][0] === '\\') {
            $varType[1] = mb_substr($varType[1], 1);
        }

        return $varType[1] ?? '';
    }
}
