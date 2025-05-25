<?php

namespace App\Shift\Rector\Shifter\Changeds;

use PhpParser\Node;
use PhpParser\Node\Identifier;
use Rector\Core\Rector\AbstractRector;
use Rector\Core\RectorDefinition\RectorDefinition;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Name\FullyQualified;
use Rector\Renaming\ValueObject\RenameClassAndConstFetch;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

class RectorRule6 extends AbstractRector
{
    public function getNodeTypes(): array
    {
        return [ClassConstFetch::class];
    }
    public function refactor(Node $node): ?Node
    {
        $refactor = new RenameClassAndConstFetch('Monolog\Logger', 'ALERT', 'Monolog\Level', 'Alert');
        if (!$this->isObjectType($node->class, $refactor->getOldObjectType())) {
            return null;
        }
        if (!$this->isName($node->name, $refactor->getOldConstant())) {
            return null;
        }

        return $this->createClassAndConstFetch($refactor);

    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('RefChangeRectorRule', [
            new CodeSample(
                'Monolog\Logger::ALERT',
                'Monolog\Level::Alert'
            )
        ]);
    }

    private function createClassAndConstFetch(RenameClassAndConstFetch $renameClassAndConstFetch) : ClassConstFetch
    {
        return new ClassConstFetch(new FullyQualified($renameClassAndConstFetch->getNewClass()), new Identifier($renameClassAndConstFetch->getNewConstant()));
    }
}
