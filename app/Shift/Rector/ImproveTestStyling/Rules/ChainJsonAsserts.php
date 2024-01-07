<?php

namespace App\Shift\Rector\ImproveTestStyling\Rules;

use PhpParser\Node;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Stmt\ClassMethod;
use Rector\Core\PhpParser\Node\BetterNodeFinder;
use Rector\Core\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

class ChainJsonAsserts extends AbstractRector
{
    public function __construct(private BetterNodeFinder $nodeFinder)
    {
    }

    public function getNodeTypes(): array
    {
        return [ClassMethod::class];
    }

    /** @param  ClassMethod  $node */
    public function refactor(Node $node): ?Node
    {
        $this->traverseNodesWithCallable($node->stmts, function (&$stmnt) use ($node) {
            if (
                $stmnt instanceof Node\Expr\MethodCall
                && $this->isName($stmnt->var, 'this')
                && $this->isNames($stmnt->name, ['assertEquals'])
            ) {
                if ($this->isAssertingResponse($node, $stmnt->args[1])) {
                    $expectedArg = $stmnt->args[0];
                    $this->traverseNodesWithCallable($node->stmts, function ($statement) use ($node, $expectedArg, &$stmnt) {
                        if ($statement instanceof Node\Expr\Assign) {
                            $assignsRequest = $this->betterNodeFinder->findFirst($statement, function ($stmnt) {
                                return $stmnt instanceof Node\Expr\MethodCall && $this->isNames($stmnt->name, ['getJson', 'patchJson', 'postJson']);
                            });
                            if (isset($assignsRequest)) {
                                $assertJsonNode = $this->betterNodeFinder->findFirst($statement, function ($stmnt) {
                                    return $stmnt instanceof Node\Expr\MethodCall && $this->isName($stmnt->name, 'assertExactJson');
                                });
                                if (! isset($assertJsonNode)) {
                                    $argument = new Node\Arg(new Node\Expr\MethodCall(new Variable('this'), 'jsonFileContentsAsArray', [$this->requestContent($node, $expectedArg)]));
                                    $statement->expr = new Node\Expr\MethodCall($statement->expr, 'assertExactJson', [$argument]);
                                    $this->removeUnnecesarryInstances($node, $stmnt);
                                    $stmnt = new Node\Expr\Empty_(new Variable(''));
                                }
                            }
                        }

                        return $statement;
                    });
                }
            }

            return $stmnt;
        });

        return null;
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('If possible use chained assertExactJson instead of asserting equal', [
            new CodeSample(

                'public function testSomething() {
                    $response = $this->getJson($url);
                    $expectedResponse = json_decode($someFilePath . \'.json\');
                    $response = json_decode($response->getContent());
                    $this->assertEqual($expectedResponse, $response);
                 }',

                'public function testSomething() {
                    $this->getJson($url)->assertExactJson(json_decode($someFilePath . \'.json\'));
                }'
            ),
        ]);
    }

    private function requestContent(ClassMethod $classMethod, Node\Arg $argument)
    {
        $node = $this->betterNodeFinder->findFirst($classMethod, function ($node) use ($argument) {
            if (
                $node instanceof Node\Expr\Assign
                && $this->isName($node->var, $argument->value->name)
                && $this->isNames($node->expr->name, ['json_decode', 'Safe\json_decode'])
            ) {
                return $node;
            }
        });

        return $node->expr->args[0]->value->args[0];
    }

    private function isAssertingResponse(ClassMethod $classMethod, Node\Arg $argument)
    {
        $node = $this->betterNodeFinder->findFirst($classMethod, function ($node) use ($argument) {
            if (
                $node instanceof Node\Expr\Assign
                && $this->isName($node->var, $argument->value->name)
                && $this->isNames($node->expr->name, ['json_decode', 'Safe\json_decode'])
                && $node->expr->args[0]->value->name->name === 'getContent'
            ) {
                return $node;
            }
        });

        return isset($node);

    }

    private function removeUnnecesarryInstances(ClassMethod|Node $node, $nodeToDelete)
    {
        $this->traverseNodesWithCallable($node, function ($stmt) use ($nodeToDelete) {
            if ($stmt instanceof Node\Expr\Assign
                && ($this->isName($stmt->var, $nodeToDelete->args[0]->value->name)
                || $this->isName($stmt->var, $nodeToDelete->args[1]->value->name))
            ) {
                return new Node\Expr\Empty_(new Variable(''));
            }
        });
        $this->traverseNodesWithCallable($node, function ($stmt) use ($nodeToDelete) {
            if ($stmt == $nodeToDelete
            ) {
                return new Node\Expr\Empty_(new Variable(''));
            }
        });
    }
}
