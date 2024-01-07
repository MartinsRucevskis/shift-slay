<?php

namespace App\Shift\Rector\CodeceptionToLaravel\RulesFirstRun;

use PhpParser\Node;
use PhpParser\Node\Expr\Variable;
use PHPStan\Node\Method\MethodCall;
use Rector\Core\PhpParser\Node\BetterNodeFinder;
use Rector\Core\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

class RefactorJsonFilesToArray extends AbstractRector
{
    public function __construct(BetterNodeFinder $nodeFinder)
    {
    }

    private array $sendMethods = ['sendGET', 'sendPOST', 'sendPATCH', 'sendDELETE', 'getJson', 'postJson', 'patchJson', 'deleteJson', 'sendGet', 'sendPatch', 'sendDelete', 'sendPost'];

    public function getNodeTypes(): array
    {
        return [Node\Expr\MethodCall::class];
    }

    /** @var MethodCall */
    public function refactor(Node $node): ?Node
    {
        if (! $this->isNames($node->name, $this->sendMethods)) {
            return null;
        }
        $this->traverseNodesWithCallable($node->args, function ($argument) {
            if (! $argument instanceof Node\Expr\FuncCall || ! $this->isNames($argument->name, ['file_get_contents', 'Safe\file_get_contents'])) {
                return $argument;
            }
            $strings = $this->betterNodeFinder->findInstanceOf($argument->args, Node\Scalar\String_::class);
            if (! isset($strings)) {
                return $argument;
            }
            $fileName = array_pop($strings);
            if (! $fileName instanceof Node\Scalar\String_ || ! str_ends_with($fileName->value, '.json')) {
                return $argument;
            }

            return new Node\Expr\MethodCall(new Variable('this'), 'jsonFileContentAsArray', $argument->args);
        });

        return $node;
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Covert Json files to array when sending requests', [
            new CodeSample(

                '$this->postJson(\'endpoint\', file_get_contents(\'jsonFile.json\'))',

                '$this->postJson(\'endpoint\', $this->jsonFileContentsAsArray(\'jsonFile.json\')'
            ),
        ]);
    }
}
