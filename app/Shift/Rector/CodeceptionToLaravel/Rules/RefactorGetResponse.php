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

class RefactorGetResponse extends AbstractRector
{

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
        if ($this->getName($node->name) !== 'grabResponse') {
            return null;
        }
        $node->var = new Variable('response');
        $node->name->name = 'getContent';

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
