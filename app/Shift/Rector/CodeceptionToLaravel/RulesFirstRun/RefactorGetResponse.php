<?php

namespace App\Shift\Rector\CodeceptionToLaravel\RulesFirstRun;

use PhpParser\Node;
use PhpParser\Node\Expr\Variable;
use PHPStan\Type\ObjectType;
use Rector\Core\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

class RefactorGetResponse extends AbstractRector
{
    public function getNodeTypes(): array
    {
        return [Node\Expr\MethodCall::class];
    }

    public function refactor(Node $node): ?Node
    {
        if (! $this->nodeTypeResolver->isMethodStaticCallOrClassMethodObjectType($node, new ObjectType('ApiTester'))) {
            return null;
        }
        if ($this->getName($node->name) !== 'grabResponse') {
            return null;
        }
        $node->var = new Variable('response');
        $node->name->name = 'getContent';

        return $node;
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Change access to response body', [
            new CodeSample(

                '$response = $I->grabResponse();',

                '$response = $response->getContent();'
            ),
        ]);
    }
}
