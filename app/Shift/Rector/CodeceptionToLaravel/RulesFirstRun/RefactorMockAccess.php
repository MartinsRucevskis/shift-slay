<?php

namespace App\Shift\Rector\CodeceptionToLaravel\RulesFirstRun;

use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Stmt\ClassMethod;
use Rector\Core\PhpParser\Node\BetterNodeFinder;
use Rector\Core\Rector\AbstractRector;
use Rector\NodeTypeResolver\NodeTypeResolver;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

class RefactorMockAccess extends AbstractRector
{
    private array $phiremockRequestConditions = [
        'postRequest',
        'putRequest',
        'deleteRequest',
        'patchRequest',
        'getRequest',
        'linkRequest',
        'optionsRequest',
        'fetchRequest',
        'optionsRequest',
        'deleteRequest',
        'headRequest',
    ];

    public function __construct(private BetterNodeFinder $nodeFinder, private NodeTypeResolver $resolver)
    {
    }

    public function getNodeTypes(): array
    {
        return [Node\Stmt\Class_::class];
    }

    /** @param  Node\Stmt\Class_  $node */
    public function refactor(Node $node): ?Node
    {
        $classMethods = array_filter($node->stmts, function ($stmnt) {
            return $stmnt instanceof ClassMethod;
        });
        foreach ($classMethods as $method) {
            $this->traverseNodesWithCallable($method, function ($methodNode) use ($classMethods) {
                if (isset($methodNode->name->name) && $methodNode->name->name === 'grabRequestsMadeToRemoteService') {
                    $condition = $methodNode->args[0]->value;
                    if ($condition->var->name === 'this') {
                        $method = $this->nodeFinder->find($classMethods, function ($node) use ($condition) {
                            return $node instanceof ClassMethod && $node->name->name === $condition->name->name;
                        });
                        $condition = $method[0]->stmts;
                    }
                    $requestMethodNode = $this->nodeFinder->findFirst($condition, function ($stmt) {
                        return ($stmt instanceof MethodCall || $stmt instanceof Node\Expr\StaticCall) && in_array($stmt->name,
                            $this->phiremockRequestConditions);
                    });
                    $requestUrls = $this->nodeFinder->findFirst($condition, function ($stmt) {
                        return $stmt instanceof MethodCall && $stmt->name->name === 'andUrl';
                    });
                    $bodyCondition = $this->nodeFinder->findFirst($condition, function ($stmt) {
                        return $stmt instanceof MethodCall && $stmt->name->name === 'andBody';
                    });
                    $strictBody = $bodyCondition->args[0]->value->name->name ?? '' === 'equalTo';
                    $toUrl = $requestUrls->args[0]->value->args[0]->value;
                    $toUrl = $toUrl instanceof Node\Scalar\String_ ? $toUrl->value : $toUrl;
                    $withMethod = strtoupper(str_replace('Request', '', $requestMethodNode->name->name));
                    $body = $bodyCondition->args[0]->value->args[0]->value ?? null;
                    $body = $body instanceof Node\Scalar\String_ ? $body->value ?? null : $body;
                    $methodNode = new MethodCall(
                        new Node\Expr\Variable('this'),
                        'outgoingRequests',
                        [
                            $this->nodeFactory->createArg($toUrl ?? ''),
                            ...(! empty($withMethod) ? [$this->nodeFactory->createArg($withMethod)] : []),
                            ...(isset($body) ? [$this->nodeFactory->createArg($body), $this->nodeFactory->createArg($strictBody)] : []),
                        ]
                    );

                    return $methodNode;
                }

                return $methodNode;
            });
        }

        return $node;
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Refactor made request retrieving from phiremock to HttpOutgoingRequestRetriever trait', [
            new CodeSample(

                '
                public function testSomething(): void {
                    $I->grabRequestsMadeToRemoteService($this->requestProxy())
                 }

                 private function requestProxy(): ConditionsBuilder{
                    return A::postRequest()->andUrl(Is::equalTo(\'someUrl\'));
                 }',

                '
                public function testSomething(): void {
                    $this->outgoingRequests(\'someUrl\', \'POST\')
                }'
            ),
        ]);
    }
}
