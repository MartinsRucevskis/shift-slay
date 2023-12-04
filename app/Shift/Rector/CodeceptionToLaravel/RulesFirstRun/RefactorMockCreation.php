<?php

namespace App\Shift\Rector\CodeceptionToLaravel\RulesFirstRun;

use PhpParser\Node;
use Rector\Core\PhpParser\Node\BetterNodeFinder;
use Rector\Core\Rector\AbstractRector;
use Rector\NodeTypeResolver\NodeTypeResolver;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

class RefactorMockCreation extends AbstractRector
{
    public function __construct(private BetterNodeFinder $nodeFinder, private NodeTypeResolver $resolver)
    {
    }

    public function getNodeTypes(): array
    {
        return [Node\Stmt\ClassMethod::class];
    }

    /** @param  Node\Stmt\ClassMethod  $node */
    public function refactor(Node $node): ?Node
    {
        if ($this->getName($node) !== 'expectARequestToRemoteServiceWithAResponse') {
            return null;
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
