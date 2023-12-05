<?php

namespace App\Shift\Rector\CodeceptionToLaravel\RulesFirstRun;

use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Stmt\ClassMethod;
use Rector\Core\PhpParser\Node\BetterNodeFinder;
use Rector\Core\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

class RefactorMockCreation extends AbstractRector
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

    public function __construct(private BetterNodeFinder $nodeFinder)
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
        $this->traverseNodesWithCallable($node->stmts, function ($nodeStatement) use ($classMethods) {
            if ($nodeStatement instanceof Node\Expr\MethodCall && $nodeStatement->name->name === 'expectARequestToRemoteServiceWithAResponse') {
                $response = $nodeStatement->args[0]->value->args[0];
                $condition = $nodeStatement->args[0]->value->var->args[0];

                $responseBody = $this->body($response, $classMethods);
                $responseStatusCode = $this->statusCode($response, $classMethods);
                $conditionUrl = $this->url($condition, $classMethods);
                $conditionBody = $this->nodeFactory->createArg(
                    $this->body($condition, $classMethods)->value->args[0]->value
                );
                $conditionMethod = $this->method($condition, $classMethods);
                $mockResponse = $this->mockConditions($conditionMethod, $conditionBody);
                $mockResponse = $this->mockResponse($mockResponse, $responseBody, $responseStatusCode);
                if (str_ends_with($this->file->getFilePath(), 'Cest.php')) {
                    $methodVariable = 'this';
                } else {
                    $methodVariable = 'this->testCase';
                }
                $mockMethod = $this->nodeFactory->createMethodCall($methodVariable, 'httpMock', [$conditionUrl]);

                return $this->nodeFactory->createMethodCall($mockMethod, 'addResponse', [$mockResponse]);

                //                $this->nodeFactory->createMethodCall($mockMethod, 'addResponse')
                return $this->nodeFactory->createMethodCall('this->httpMock('.$conditionUrl.')->addResponse($this->fakeHttpResponse()->when()->postRequest()->withBody(\'someBody\')->then()->respond()->withStatusCode(200)->andBody(file_get_contents(\'mockPath\')))', '');

            }
        });

        return $node;
    }

    private function mockResponse(MethodCall $methodCall, $responseBody, $responseStatusCode)
    {
        if (isset($responseStatusCode)) {
            $methodCall = $this->nodeFactory->createMethodCall($methodCall, 'respondWithStatusCode', [$responseStatusCode]);
        }
        if (isset($responseBody)) {
            $methodCall = $this->nodeFactory->createMethodCall($methodCall, 'respondWithBody', [$responseBody]);
        }

        return $methodCall;
    }

    private function mockConditions(?string $conditionMethod, $conditionBody)
    {
        if (str_ends_with($this->file->getFilePath(), 'Cest.php')) {
            $mock = 'this->httpMockResponse()';
        } else {
            $mock = 'this->testCase->httpMockResponse()';
        }
        $mockEndMethod = '';
        $mockEndingParams = [];
        if (empty($conditionMethod) && ! isset($conditionBody)) {
            return $this->nodeFactory->createMethodCall($mock, $mockEndMethod, []);
        }
        $mock .= '->when()';
        if (! empty($conditionMethod)) {
            $mock .= '->'.$conditionMethod.'()';
        }
        if (isset($conditionBody) && $this->getName($conditionBody->value) !== 'null') {
            $mockEndMethod = 'bodyIsContaining';
            $mockEndingParams[] = $conditionBody;
        }

        if (! empty($mockEndMethod)) {
            return $this->nodeFactory->createMethodCall(
                $this->nodeFactory->createMethodCall($mock, $mockEndMethod, $mockEndingParams),
                'then',
                []
            );
        }

        return $this->nodeFactory->createMethodCall($mock, 'then', []);
    }

    private function url(Node $node, array $classMethods): mixed
    {

        $url = $this->nodeFinder->findFirst($node, function ($stmt) {
            return $stmt instanceof MethodCall && $stmt->name->name === 'andUrl';
        });

        if (! isset($url)) {
            $callToSelf = $this->nodeFinder->findFirst($node, function ($stmt) {
                return $this->getName($stmt) === 'this';
            });
            if (isset($callToSelf)) {
                $node = $this->findMethod($node, $classMethods);
                $url = $this->nodeFinder->findFirst($node, function ($stmt) {
                    return $stmt instanceof MethodCall && $stmt->name->name === 'andUrl';
                });
            }
        }

        return $this->nodeFactory->createArg($url->args[0]->value->args[0]->value);
    }

    private function body(Node $node, array $classMethods): mixed
    {
        $body = $this->nodeFinder->findFirst($node, function ($stmt) {
            return $stmt instanceof MethodCall && $stmt->name->name === 'andBody';
        });

        if (! isset($body)) {
            $callToSelf = $this->nodeFinder->findFirst($node, function ($stmt) {
                return $this->getName($stmt) === 'this';
            });
            if (isset($callToSelf)) {
                $node = $this->findMethod($node, $classMethods);
                $body = $this->nodeFinder->findFirst($node, function ($stmt) {
                    return $stmt instanceof MethodCall && $stmt->name->name === 'andBody';
                });
            }
        }

        return $this->nodeFactory->createArg($body->args[0]->value);
    }

    private function method(Node $node, array $classMethods): mixed
    {
        $method = $this->nodeFinder->findFirst($node, function ($stmt) {
            return $stmt instanceof Node\Expr\StaticCall && in_array($stmt->name,
                $this->phiremockRequestConditions);
        });

        if (! isset($method)) {
            $callToSelf = $this->nodeFinder->findFirst($node, function ($stmt) {
                return $this->getName($stmt) === 'this';
            });
            if (isset($callToSelf)) {
                $node = $this->findMethod($node, $classMethods);
                $method = $this->nodeFinder->findFirst($node, function ($stmt) {
                    return $stmt instanceof Node\Expr\StaticCall && in_array($stmt->name,
                        $this->phiremockRequestConditions);
                });
            }
        }

        return str_replace('Request', '', $method->name->name);
    }

    private function headers(Node $node, array $classMethods): mixed
    {
        $headers = $this->nodeFinder->findFirst($node, function ($stmt) {
            return $stmt instanceof MethodCall && $stmt->name->name === 'andHeader';
        });

        if (! isset($headers)) {
            $callToSelf = $this->nodeFinder->findFirst($node, function ($stmt) {
                return $this->getName($stmt) === 'this';
            });
            if (isset($callToSelf)) {
                $node = $this->findMethod($node, $classMethods);
                $headers = $this->nodeFinder->findFirst($node, function ($stmt) {
                    return $stmt instanceof MethodCall && $stmt->name->name === 'andHeader';
                });
            }
        }
        $headers = $headers->args[0]->value;

        return $this->nodeFactory->createArg($headers);
    }

    private function statusCode(Node $node, array $classMethods): mixed
    {
        $statusCode = $this->nodeFinder->findFirst($node, function ($stmt) {
            return $stmt instanceof Node\Expr\StaticCall && $stmt->name->name === 'withStatusCode';
        });

        if (! isset($statusCode)) {
            $callToSelf = $this->nodeFinder->findFirst($node, function ($stmt) {
                return $this->getName($stmt) === 'this';
            });
            if (isset($callToSelf)) {
                $node = $this->findMethod($node, $classMethods);
                $statusCode = $this->nodeFinder->findFirst($node, function ($stmt) {
                    return $stmt instanceof Node\Expr\StaticCall && $stmt->name->name === 'withStatusCode';
                });
            }
        }
        $statusCode = $statusCode->args[0]->value;

        return $this->nodeFactory->createArg($statusCode);
    }

    private function findMethod(Node $methodCallNode, array $classMethods)
    {
        $methodCallNode = $this->nodeFinder->findFirst($methodCallNode, function ($node) {
            return $node->var->name === 'this';
        });
        $method = $this->nodeFinder->findFirst($classMethods, function ($node) use ($methodCallNode) {
            return $node instanceof ClassMethod && $node->name->name === $methodCallNode->name->name;
        });
        $condition = $method->stmts;

        return $condition;
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

    private function isTestCase(Node|Node\Stmt\Class_ $node)
    {
    }
}
