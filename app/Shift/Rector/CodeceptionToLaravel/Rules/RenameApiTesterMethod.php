<?php


namespace App\Shift\Rector\CodeceptionToLaravel\Rules;

use Illuminate\Database\Query\Expression;
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
        'sendPOST' => 'postJson',
        'sendPATCH' => 'patchJson',
        'sendDELETE' => 'deleteJson',
    ];

    public function getNodeTypes(): array
    {
        return [Node\Expr\MethodCall::class];
    }

    public function refactor(Node $node): ?Node
    {
        if (!$this->nodeTypeResolver->isMethodStaticCallOrClassMethodObjectType($node, new ObjectType('ApiTester'))) {
            return null;
        }
        $rename = $this->methodRenames[$this->getName($node->name)] ?? null;
        if (!isset($rename)) {
            return null;
        }
        $node->name = new Node\Identifier($rename);
        if(in_array($node->name, ['getJson', 'postJson', 'patchJson', 'deleteJson'])){
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
