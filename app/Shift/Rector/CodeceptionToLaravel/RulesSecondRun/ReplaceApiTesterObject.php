<?php

namespace App\Shift\Rector\CodeceptionToLaravel\RulesSecondRun;

use PhpParser\Builder\Param;
use PhpParser\Node;
use PhpParser\Node\Expr\Variable;
use Rector\Core\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

// Will generate diff as (something)->chainedCall(), but actually will be converted to something->chainedCall(), due to afterTraverse regex modification
class ReplaceApiTesterObject extends AbstractRector
{
    public function getNodeTypes(): array
    {
        return [Node\Stmt\Class_::class];
    }

    /**
     * @param  Node\Stmt\Class_  $node
     */
    public function refactor(Node $node): ?Node
    {
        if (str_ends_with($node->name->name, 'Cest') || str_ends_with($node->name->name, 'Test')) {
            return null;
        }
        $this->traverseNodesWithCallable($node->stmts, function (Node $node) {
            if ($node instanceof Variable) {
                return $node;
            }
        });

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
