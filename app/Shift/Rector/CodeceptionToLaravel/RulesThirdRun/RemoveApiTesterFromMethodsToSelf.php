<?php

namespace App\Shift\Rector\CodeceptionToLaravel\RulesThirdRun;

use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use Rector\Core\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

class RemoveApiTesterFromMethodsToSelf extends AbstractRector
{
    public function getNodeTypes(): array
    {
        return [MethodCall::class];
    }

    /** @param  MethodCall  $node */
    public function refactor(Node $node): ?Node
    {
        if ($node->var->name === 'this') {
            foreach ($node->args as $key => $arg) {
                if ($arg->value->name === 'I') {
                    unset($node->args[$key]);
                }
            }
            $node->args = array_values($node->args);
        }

        return $node;
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Removes codeception api tester.', [
            new CodeSample(

                '$this->method($I);',

                '$this->method();'
            ),
        ]);
    }
}
