<?php

namespace App\Shift\Rector\CodeceptionToLaravel\RulesFirstRun;

use PhpParser\Node;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Stmt\ClassMethod;
use Rector\Core\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

// Will generate diff as (something)->chainedCall(), but actually will be converted to something->chainedCall(), due to afterTraverse regex modification
class AddResponseAsParamWhenCaller extends AbstractRector
{
    public function getNodeTypes(): array
    {
        return [ClassMethod::class];
    }

    /** @param  ClassMethod  $node */
    public function refactor(Node $node): ?Node
    {
        if (! $this->hasResponseVariable($node) || $this->hasResponseParameter($node)) {
            return null;
        }

        $responseParam = new Node\Param(new Variable('response'), type: new Node\Name('Illuminate\Testing\TestResponse'));
        $node->params[] = $responseParam;

        return $node;
    }

    private function hasResponseVariable(ClassMethod $node): bool
    {
        return (bool) $this->betterNodeFinder->findFirst($node->stmts, function (Node $node): bool {
            return $node instanceof Variable && $this->getName($node) === 'response';
        });
    }

    private function hasResponseParameter(ClassMethod $node): bool
    {
        foreach ($node->params as $param) {
            if ($this->getName($param->var) === 'response') {
                return true;
            }
        }

        return false;
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
