<?php

declare(strict_types=1);

namespace App\Shift\Objects;

use PhpParser\Node\Expr;
use PhpParser\Node\Param;

class MethodParam
{
    public ?string $type;

    public string $name;

    public function __construct(Param $property)
    {
        $propertyName = $property->var->name ?? '';
        if ($propertyName instanceof Expr) {
            $propertyName = $propertyName->getType();
        }

        $this->name = $propertyName;
        $type = $property->type;
        $name = null;

        if ($type !== null) {
            $name = $type->name ?? null;

            if ($name === null && method_exists($type, 'getParts')) {
                $parts = $type->getParts();
                $name = $parts[0] ?? null;
            }
        }

        $this->type = $name;
    }
}
