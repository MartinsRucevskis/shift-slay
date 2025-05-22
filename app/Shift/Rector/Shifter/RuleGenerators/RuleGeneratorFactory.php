<?php

namespace App\Shift\Rector\Shifter\RuleGenerators;

use PHPSemVerChecker\Operation\ClassMethodImplementationChanged;
use PHPSemVerChecker\Operation\ClassMethodParameterTypingAdded;
use PHPSemVerChecker\Operation\Operation;

class RuleGeneratorFactory
{
    public function createRuleGenerator(Operation $operation): ?Rule
    {
        return match (get_class($operation)) {
            ClassMethodParameterTypingAdded::class => new TypeAddedWarning($operation),
            ClassMethodImplementationChanged::class => new ClassMethodImplementationChangedRule($operation),
            default => null
        };
    }
}
