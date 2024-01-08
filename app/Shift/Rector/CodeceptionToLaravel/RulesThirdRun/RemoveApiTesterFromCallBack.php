<?php

namespace App\Shift\Rector\CodeceptionToLaravel\RulesThirdRun;

use PhpParser\Node;
use Rector\Core\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

// Will generate diff as (something)->chainedCall(), but actually will be converted to something->chainedCall(), due to afterTraverse regex modification
class RemoveApiTesterFromCallBack extends AbstractRector
{
    public function getNodeTypes(): array
    {
        return [Node\Expr\Closure::class];
    }

    /** @param  Node\Expr\Closure  $node */
    public function refactor(Node $node): ?Node
    {
        $uses = $node->uses;
        foreach ($uses as $key => $use) {
            if ($this->isName($use->var, 'I')) {
                unset($uses[$key]);
            }
        }
        $node->uses = array_values($uses);

        return $node;
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Removes Codeceptions Api tester', [
            new CodeSample(

                'function() use($I){
                }',

                'function(){
                }'
            ),
        ]);
    }
}
