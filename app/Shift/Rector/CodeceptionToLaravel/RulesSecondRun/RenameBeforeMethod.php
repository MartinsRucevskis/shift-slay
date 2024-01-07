<?php

namespace App\Shift\Rector\CodeceptionToLaravel\RulesSecondRun;

use PhpParser\Node;
use PhpParser\Node\Name;
use Rector\Core\Rector\AbstractRector;
use Rector\Core\ValueObject\Visibility;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

class RenameBeforeMethod extends AbstractRector
{
    public function getNodeTypes(): array
    {
        return [Node\Stmt\ClassMethod::class];
    }

    public function refactor(Node $node): ?Node
    {
        if ($this->getName($node) === '_before') {
            $stmts[] = new Node\Stmt\Expression(new Node\Expr\StaticCall(new Name('Parent'), 'setUp'));
            $stmts = array_merge($stmts, $node->stmts);
            $node->stmts = $stmts;
            $node->flags = Visibility::PROTECTED;
            $node->name->name = 'setUp';
            $node->params = [];

        }

        return $node;
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Rename Codeception before method', [
            new CodeSample(

                'public function __before(ApiTester $I)
                {
                    $this->I = $I;
                }',

                'protected function setUp(){
                    Parent::setup();
                    $this->I = $I;
                }'
            ),
        ]);
    }
}
