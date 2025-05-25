<?php

namespace App\Shift\Rector\Shifter\RuleGenerators;

use PHPSemVerChecker\Operation\Operation;

class PropertyRemovedWarning implements  Rule
{
    public function __construct(private Operation $operation)
    {
    }

    public function generateRule(): void
    {

    }
}
