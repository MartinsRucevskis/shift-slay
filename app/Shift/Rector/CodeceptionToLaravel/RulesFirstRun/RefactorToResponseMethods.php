<?php

namespace App\Shift\Rector\CodeceptionToLaravel\RulesFirstRun;

use PhpParser\Node;
use PhpParser\Node\Expr\Variable;
use PHPStan\Type\ObjectType;
use Rector\Core\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

class RefactorToResponseMethods extends AbstractRector
{
    private array $methodRenames = [
        'grabDataFromResponseByJsonPath' => 'json',
    ];

    public function getNodeTypes(): array
    {
        return [Node\Expr\MethodCall::class];
    }

    public function refactor(Node $node): ?Node
    {
        if (
            ! $this->nodeTypeResolver->isMethodStaticCallOrClassMethodObjectType($node, new ObjectType('ApiTester'))
            && ! $this->nodeTypeResolver->isMethodStaticCallOrClassMethodObjectType($node, new ObjectType('Unit'))
        ) {
            return null;
        }
        $rename = $this->methodRenames[$this->getName($node->name)] ?? null;
        if (! isset($rename)) {
            return null;
        }
        $node->name = new Node\Identifier($rename);
        $node->var = new Variable('response');

        return $node;
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Replace json path access to equivalent in Laravel feature tests', [
            new CodeSample(

                '$I->grabDataFromResponseByJsonPath(\'some.path\')',

                '$response->json(\'some.path\')'
            ),
        ]);
    }
}
