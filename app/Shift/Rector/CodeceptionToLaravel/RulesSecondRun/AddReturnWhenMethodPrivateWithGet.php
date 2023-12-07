<?php

namespace App\Shift\Rector\CodeceptionToLaravel\RulesSecondRun;

use PhpParser\Builder\Param;
use PhpParser\Node;
use Rector\Core\PhpParser\Node\BetterNodeFinder;
use Rector\Core\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

class AddReturnWhenMethodPrivateWithGet extends AbstractRector
{
    public function __construct(private BetterNodeFinder $nodeFinder)
    {
    }

    public function getNodeTypes(): array
    {
        return [Node\Stmt\Class_::class];
    }

    /**
     * @param  Node\Stmt\Class_  $node
     */
    public function refactor(Node $node): ?Node
    {
        $methods = $this->nodeFinder->findInstanceOf($node, Node\Stmt\ClassMethod::class);
        foreach ($methods as $method) {
            if ($method->isPrivate()) {
                $lastNode = $method->stmts[count($method->stmts) - 1];
                if ($lastNode->expr->expr instanceof Node\Expr\MethodCall && in_array($lastNode->expr->expr->name->name, ['postJson', 'getJson', 'patchJson', 'deleteJson'])) {
                    $this->traverseNodesWithCallable($node, function ($stmnt) use ($method, $node) {
                        if ($stmnt instanceof Node\Expr\MethodCall && $this->getName($method) === $stmnt->name->name) {
                            $sameNodeAssigned = $this->betterNodeFinder->find($node, function ($nodeStatements) use ($stmnt) {
                                return $nodeStatements instanceof Node\Expr\Assign
                                    && $this->getName($nodeStatements->var) === 'response'
                                    && $nodeStatements->expr === $stmnt;
                            });
                            if (count($sameNodeAssigned) === 0) {
                                return new Node\Expr\Assign(new Node\Expr\Variable('response'), $stmnt);
                            }
                        }
                    });
                    $this->traverseNodesWithCallable($node, function ($stmnt) use ($lastNode) {
                        if ($stmnt === $lastNode) {
                            return new Node\Stmt\Return_($stmnt->expr->expr);
                        }
                    });
                    $this->traverseNodesWithCallable($node, function ($stmnt) use ($method) {
                        if ($stmnt === $method) {
                            $method->returnType = new Node\Name('Illuminate\Testing\TestResponse');
                        }
                    });
                }
            }
        }

        return null;
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
