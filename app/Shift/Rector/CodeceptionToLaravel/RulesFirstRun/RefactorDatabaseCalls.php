<?php

namespace App\Shift\Rector\CodeceptionToLaravel\RulesFirstRun;

use PhpParser\Node;
use PhpParser\Node\Expr\Variable;
use Rector\Core\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

class RefactorDatabaseCalls extends AbstractRector
{
    public function getNodeTypes(): array
    {
        return [Node\Expr\New_::class, Node\Expr\StaticCall::class];
    }

    /**
     * @param  Node\Expr\New_|Node\Expr\StaticCall  $node
     */
    public function refactor(Node $node): ?Node
    {
        if (! str_ends_with($this->file->getFilePath(), 'Cest.php')) {
            return null;
        }
        $methodCallArgs = $node->getArgs();
        foreach ($methodCallArgs as $key => $arg) {
            if ($this->isName($arg->value, 'I')) {
                $methodCallArgs[$key] = new Node\Arg(new Variable('this'));
            }
        }
        $node->args = $methodCallArgs;

        return $node;
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('asd', [new CodeSample('asd', 'a')]);
        // TODO: Implement getRuleDefinition() method.
    }
}
