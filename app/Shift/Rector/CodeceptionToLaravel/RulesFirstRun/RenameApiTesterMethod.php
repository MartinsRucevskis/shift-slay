<?php

namespace App\Shift\Rector\CodeceptionToLaravel\RulesFirstRun;

use PhpParser\Node;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\Variable;
use PHPStan\Type\ObjectType;
use Rector\Core\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

class RenameApiTesterMethod extends AbstractRector
{
    private array $methodRenames = [
        'haveHttpHeader' => 'withHeader',
        'sendGET' => 'getJson',
        'sendGet' => 'getJson',
        'sendPOST' => 'postJson',
        'sendPost' => 'postJson',
        'sendPATCH' => 'patchJson',
        'sendPatch' => 'patchJson',
        'sendDELETE' => 'deleteJson',
        'sendDelete' => 'deleteJson',
        'canSeeInDatabase' => 'assertDatabaseHas',
        'dontSeeInDatabase' => 'assertDatabaseMissing',
        'assertLessOrEquals' => 'assertLessThanOrEqual'
    ];
    //        $record = $this->grabFromDatabase('logs', 'message'); japarveido

    public function getNodeTypes(): array
    {
        return [Node\Expr\MethodCall::class];
    }

    public function refactor(Node $node): ?Node
    {
        if (
            !$this->nodeTypeResolver->isMethodStaticCallOrClassMethodObjectType($node, new ObjectType('ApiTester'))
            && !$this->nodeTypeResolver->isMethodStaticCallOrClassMethodObjectType($node, new ObjectType('Unit'))
        ) {
            return null;
        }
        $rename = $this->methodRenames[$this->getName($node->name)] ?? null;
        if (! isset($rename)) {
            return null;
        }
        $node->name = new Node\Identifier($rename);
        if (in_array($node->name, ['getJson', 'postJson', 'patchJson', 'deleteJson'])) {
            /** @var MethodCall $node */
            $variable = new Variable('response');
            $node = new Assign($variable, $node);
        }

        return $node;
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Upgrade Monolog method signatures and array usage to object usage', [
            new CodeSample(
                // code before
                'public function handle(array $record) { return $record[\'context\']; }',
                // code after
                'public function handle(\Monolog\LogRecord $record) { return $record->context; }'
            ),
        ]);
    }
}
