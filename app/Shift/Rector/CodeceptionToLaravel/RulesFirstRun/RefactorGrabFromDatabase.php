<?php

namespace App\Shift\Rector\CodeceptionToLaravel\RulesFirstRun;

use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Type\ObjectType;
use Rector\Core\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

class RefactorGrabFromDatabase extends AbstractRector
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
        if ($this->getName($node->name) !== 'grabFromDatabase') {
            return null;
        }
        $methodArguments = $node->args;
        $method = new Node\Expr\StaticCall(new Node\Name('Illuminate\Support\Facades\DB'), 'table', [$methodArguments[0]]);
        if (count($methodArguments) === 2) {
            $node = new MethodCall($method, 'get', [$methodArguments[1]]);
        } else {
            $method = new MethodCall($method, 'where', [$methodArguments[2]]);
            $node = new MethodCall($method, 'get', [$methodArguments[1]]);
        }

        return $node;
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Upgrade Monolog method signatures and array usage to object usage', [
            new CodeSample(

                '$I->grabFromDatabase(\'logs\',[\'id\' => 666])',

                'DB::table(\'logs\')->where([\'id\' => 666])'
            ),
        ]);
    }
}
