<?php

namespace App\Shift\Rector\CodeceptionToLaravel\RulesThirdRun;

use PhpParser\Builder\Param;
use PhpParser\Node;
use Rector\Core\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

class FixNameSpaces extends AbstractRector
{
    public function getNodeTypes(): array
    {
        return [Node\Stmt\Class_::class];
    }

    /**
     * @param  Node\Stmt\Class_ $node
     */
    public function refactor(Node $node): ?Node
    {
        $pathFromRoot = collect(explode('\\', str_replace(env('SHIFT_PROJECT_PATH'), '', $this->file->getFilePath())));
        $pathFromRoot = $pathFromRoot->filter(function ($path){return !empty($path);});
        $pathFromRoot = $pathFromRoot->map(function ($path){
            return ucfirst($path);
        });
        $pathFromRoot->pop();
        $pathFromRoot = $pathFromRoot->values()->toArray();
        $pathFromRoot[] = $node->name->name;
        $node->namespacedName = new Node\Name($pathFromRoot);
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
