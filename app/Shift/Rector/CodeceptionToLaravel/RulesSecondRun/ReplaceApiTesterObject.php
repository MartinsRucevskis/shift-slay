<?php

namespace App\Shift\Rector\CodeceptionToLaravel\RulesSecondRun;

use PhpParser\Node;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\Variable;
use Rector\Core\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

// Will generate diff as (something)->chainedCall(), but actually will be converted to something->chainedCall(), due to afterTraverse regex modification
class ReplaceApiTesterObject extends AbstractRector
{
    public function getNodeTypes(): array
    {
        return [PropertyFetch::class, Node\Expr\MethodCall::class];
    }

    public function refactor(Node $node): ?Node
    {
        if ($node instanceof Node\Expr\MethodCall) {
            if (str_ends_with($this->file->getFilePath(), 'Cest.php') && $node->var->name === 'I') {
                $node->var->name = 'this';

                return $node;
            }
        }
        if (! $this->isName($node, 'I')) {
            return null;
        }
        if (! str_ends_with($this->file->getFilePath(), 'Cest.php')) {
            return new PropertyFetch(new Variable('this'), 'testCase');
        }

        return new Variable('this');
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Replaces property fetch from I to testCase', [
            new CodeSample(

                '$this->I->assertEquals();',

                '$this->testCase->assertEquals()'
            ),
        ]);
    }
}
