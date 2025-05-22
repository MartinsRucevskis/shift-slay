<?php

namespace App\Shift\Rector\Shifter\RuleGenerators;

use App\Shift\Rector\Shifter\RectorGenerator;

class RefChangeGenerator extends RectorGenerator
{

    protected function createRule(array $change, string $className): string
    {
        $oldClass = $change['old_class'];
        $oldName = $change['old_name'];
        $newClass = $change['new_class'];
        $newName = $change['new_name'];
        return <<<PHP
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

class {$className} extends AbstractRector
{
    public function getNodeTypes(): array
    {
        return [ClassConstFetch::class];
    }
    public function refactor(Node \$node): ?Node
    {
        \$refactor = new RenameClassAndConstFetch('$oldClass', '$oldName', '$newClass', '$newName');
        if (!\$this->isObjectType(\$node->class, \$refactor->getOldObjectType())) {
            return null;
        }
        if (!\$this->isName(\$node->name, \$refactor->getOldConstant())) {
            return null;
        }

        return \$this->createClassAndConstFetch(\$refactor);

    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('RefChangeRectorRule', [
            new CodeSample(
                '{$oldClass}::{$oldName}',
                '{$newClass}::{$newName}'
            )
        ]);
    }

    private function createClassAndConstFetch(RenameClassAndConstFetch \$renameClassAndConstFetch) : ClassConstFetch
    {
        return new ClassConstFetch(new FullyQualified(\$renameClassAndConstFetch->getNewClass()), new Identifier(\$renameClassAndConstFetch->getNewConstant()));
    }
}

PHP;
    }

    protected function shouldGenerate(array $change): bool
    {
        return isset($change['old_class'],$change['old_name'],$change['new_class'],$change['new_name'], $change['count'])
            && $change['count'] > 5
            && !in_array('???', [$change['old_class'], $change['old_name'], $change['new_class'], $change['new_name']])
            && !($change['new_class'] === $change['old_class'] && $change['old_name'] === $change['new_name']);
    }
}
