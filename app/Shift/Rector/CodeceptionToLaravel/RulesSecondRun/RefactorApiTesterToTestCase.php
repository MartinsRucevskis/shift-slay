<?php

namespace App\Shift\Rector\CodeceptionToLaravel\RulesSecondRun;

use PhpParser\Builder\Param;
use PhpParser\Node;
use PhpParser\Node\Expr\PropertyFetch;
use PHPStan\Type\ObjectType;
use Rector\Core\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

// Will generate diff as (something)->chainedCall(), but actually will be converted to something->chainedCall(), due to afterTraverse regex modification
class RefactorApiTesterToTestCase extends AbstractRector
{
    public function getNodeTypes(): array
    {
        return [Node\Stmt\Property::class];
    }

    /**
     * @param  PropertyFetch  $node
     */
    public function refactor(Node $node): ?Node
    {
        if (str_ends_with($this->file->getFilePath(), 'Cest.php')) {
            return null;
        }

        if (! $this->isObjectType($node, new ObjectType('ApiTester'))) {
            return null;
        }
        $node->type = new \PhpParser\Node\Name('Tests\TestCase');
        $node->props[0]->name = new \PhpParser\Node\VarLikeIdentifier('testCase');

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
