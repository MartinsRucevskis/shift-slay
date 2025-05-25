<?php

namespace App\Shift\Rector\Shifter;

abstract class RectorGenerator
{
    final public function generate(array $change, string $name): ?string{
    if ($this->shouldGenerate($change)){
            return $this->createRule($change, $name);
        }
        return null;
    }

    protected abstract function shouldGenerate(array $change): bool;

    protected abstract function createRule(array $change, string $name): string;

}
