<?php

namespace App\Shift\Rector\Shifter\RuleGenerators;

use PHPSemVerChecker\Operation\Operation;

interface Rule
{
    public function __construct(Operation $operation);

    public function generateRule(): void;

}
