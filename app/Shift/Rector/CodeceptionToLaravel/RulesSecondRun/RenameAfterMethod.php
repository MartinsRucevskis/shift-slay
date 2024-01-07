<?php

namespace App\Shift\Rector\CodeceptionToLaravel\RulesSecondRun;

use PhpParser\Node;
use PhpParser\Node\Name;
use Rector\Core\Rector\AbstractRector;
use Rector\Core\ValueObject\Visibility;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

class RenameAfterMethod extends AbstractRector
{
    public function getNodeTypes(): array
    {
        return [Node\Stmt\ClassMethod::class];
    }

    public function refactor(Node $node): ?Node
    {
        if ($this->getName($node) === '__after') {
            $stmts[] = new Node\Stmt\Expression(new Node\Expr\StaticCall(new Name('Parent'), 'tearDown'));
            $stmts = array_merge($stmts, $node->stmts);
            $node->stmts = $stmts;
            $node->flags = Visibility::PROTECTED;
            $node->name->name = 'tearDown';
            $node->params = [];

        }

        return $node;
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Renames codeceptions before method to Laravel Feature tests teardown method', [
            new CodeSample(

                'public function __after(): void {}',

                'protected function tearDown(): void {}'
            ),
        ]);
    }
}
