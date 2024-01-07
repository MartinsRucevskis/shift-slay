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
            if (! $method->isPrivate()) {
                continue;
            }
            $lastNode = $method->stmts[count($method->stmts) - 1];
            if ($this->isNodeMakingRequest($lastNode)) {

                $this->traverseNodesWithCallable($node, function ($stmnt) use ($method, $node) {
                    if ($stmnt instanceof Node\Expr\MethodCall && $this->getName($method) === $stmnt->name->name) {
                        $sameNodeAssigned = $this->betterNodeFinder->find($node, function ($nodeStatements) use ($stmnt) {
                            return $nodeStatements instanceof Node\Expr\Assign
                                && $this->getName($nodeStatements->var) === 'response'
                                && $nodeStatements->expr === $stmnt;
                        });
                        if (count($sameNodeAssigned) === 0) {
                            $stmnt->args = array_values($stmnt->args);
                            $stmnt = new Node\Expr\Assign(new Node\Expr\Variable('response'), $stmnt);
                        }

                        return $stmnt;
                    }
                });

                $this->traverseNodesWithCallable($node, function ($stmnt) use ($lastNode) {
                    if ($stmnt === $lastNode) {
                        $stmnt = new Node\Stmt\Return_($stmnt->expr->expr);
                    }

                    return $stmnt;
                });

                $this->traverseNodesWithCallable($node, function ($stmnt) use ($method) {
                    if ($stmnt === $method) {
                        $stmnt->returnType = new Node\Name('Illuminate\Testing\TestResponse');
                    }

                    return $stmnt;
                });
            }
        }

        return $node;
    }

    private function isNodeMakingRequest(Node $node)
    {
        return $node->expr->expr instanceof Node\Expr\MethodCall &&
            (in_array($node->expr->expr->name->name, ['postJson', 'getJson', 'patchJson', 'deleteJson'])
            || in_array($node->expr->expr->var->name->name, ['postJson', 'getJson', 'patchJson', 'deleteJson']));
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Add return statement if method is sending a request', [
            new CodeSample(

                '
                public function testSomething(): void{
                    $this->makeEndpointRequest([]);
                }
                private function makeEndpointRequest($data): void{
                    $this->postJson($this->endpoint, $data);
                }',

                '
                public function testSomething(): void{
                    $response = $this->makeEndpointRequest([]);
                }
                private function makeEndpointRequest($data): TestResponse{
                    return $this->postJson($this->endpoint, $data);
                }',
            ),
        ]);
    }
}
