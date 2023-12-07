<?php

namespace App\Shift\Rector\CodeceptionToLaravel\RulesThirdRun;

use PhpParser\Builder\Param;
use PhpParser\Node;
use Rector\Core\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

class RemoveSelfFromClosure extends AbstractRector
{
    public function getNodeTypes(): array
    {
        return [Node\Expr\Closure::class];
    }

    /**
     * @param  Closure  $node
     */
    public function refactor(Node $node): ?Node
    {
        if (! str_ends_with($this->file->getFilePath(), 'Cest.php')) {
            return null;
        }
        foreach ($node->uses as $key => $use) {
            if ($this->isName($use->var, 'this')) {
                unset($node->uses[$key]);
            }
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
