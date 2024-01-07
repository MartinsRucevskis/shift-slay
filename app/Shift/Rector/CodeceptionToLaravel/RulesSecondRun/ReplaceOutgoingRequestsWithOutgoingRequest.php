<?php

namespace App\Shift\Rector\CodeceptionToLaravel\RulesSecondRun;

use PhpParser\Builder\Param;
use PhpParser\Node;
use PhpParser\Node\Expr\Variable;
use Rector\Core\PhpParser\Node\BetterNodeFinder;
use Rector\Core\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

class ReplaceOutgoingRequestsWithOutgoingRequest extends AbstractRector
{
    // Semi-reliable
    public function __construct(private BetterNodeFinder $nodeFinder)
    {
    }

    public function getNodeTypes(): array
    {
        return [Node\Stmt\ClassMethod::class];
    }

    /**
     * @param  Node\Stmt\ClassMethod  $node
     */
    public function refactor(Node $node): ?Node
    {
        $this->traverseNodesWithCallable($node, function ($stmnt) use ($node) {
            if ($stmnt instanceof Node\Expr\Assign && $stmnt->expr instanceof Node\Expr\MethodCall) {
                if ($this->getName($stmnt->expr->name) === 'outgoingRequests' && $stmnt->expr->var->name === 'this') {
                    $variableName = $this->getName($stmnt->var);
                    $isUsedAsVariable = $this->nodeFinder->find($node, function ($stmt) use ($variableName) {
                        return $stmt instanceof Variable && $this->getName($stmt) === $variableName;
                    });

                    $arrayAccess = $this->nodeFinder->findInstancesOf($node, [Node\Expr\ArrayDimFetch::class]);
                    if (count($isUsedAsVariable) - count($arrayAccess) <= 1) {
                        $onlyFirstElement = array_filter($arrayAccess, function ($arrayDim) use ($variableName) {
                            return $arrayDim->var->name === $variableName && $arrayDim->dim->value !== 0;
                        }) === [];
                        if ($onlyFirstElement && count($arrayAccess) > 0) {
                            $stmnt->expr->name->name = 'outgoingRequest';
                            $this->traverseNodesWithCallable($node, function ($stmnt) use ($variableName) {
                                if ($stmnt instanceof Node\Expr\ArrayDimFetch && $stmnt->var->name === $variableName) {
                                    return new Variable($variableName);
                                }
                            });
                            $this->traverseNodesWithCallable($node, function ($stmnt) use ($variableName) {
                                if ($stmnt instanceof Node\Expr\PropertyFetch && $stmnt->var->name === $variableName) {
                                    return new Node\Expr\MethodCall($stmnt->var, 'body', []);
                                }
                            });
                        }

                        return $stmnt;
                    }
                    $this->traverseNodesWithCallable($node, function ($stmnt) use ($variableName) {
                        if ($stmnt instanceof Node\Expr\PropertyFetch && $stmnt->var->name === $variableName) {
                            return new Node\Expr\MethodCall($stmnt->var, 'body', []);
                        }
                        if ($stmnt->var instanceof Node\Expr\ArrayDimFetch && $this->getName($stmnt->var->var) === $variableName) {
                            return new Node\Expr\MethodCall($stmnt->var, 'body', []);
                        }
                    });
                }
            }

            return $stmnt;
        });

        return $node;
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Replace outgoingRequests with outgoingRequest when possible', [
            new CodeSample(

                '
                use Tests\TestCase;

                class exampleClass extends TestCase
                {
                    public function someMethod(){
                        $requests = $this->outgoingRequests();
                        $this->assertEquals(\'something\', $requests[0]->body());
                        $this->assertEquals(\'something\', $requests[0]->body());
                        $requests = $this->outgoingRequests();
                        $this->assertEquals(\'something\', $requests[0]->body());
                        $this->assertEquals(\'something\', $requests[1]->body());
                    }
                ',

                '
                use Tests\TestCase;

                class exampleClass extends TestCase
                {
                    public function someMethod(){
                        $requests = $this->outgoingRequest();
                        $this->assertEquals(\'something\', $requests->body());
                        $this->assertEquals(\'something\', $requests->body());
                        $requests = $this->outgoingRequests();
                        $this->assertEquals(\'something\', $requests[0]->body());
                        $this->assertEquals(\'something\', $requests[1]->body());
                    }
                '
            ),
        ]);
    }
}
