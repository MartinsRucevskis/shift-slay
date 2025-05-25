<?php

namespace App\Shift;

use App\Shift\Enums\TypeEnums;
use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\NodeFinder;

class MethodCallChainExtensionDetector implements RefactorDetectorInterface
{
    use MyCoolHelpers;

    public function detect(string $className, string $methodName, Node $oldStmt, Node $newStmt): ?array
    {
        $finder = new NodeFinder();
        $oldMethodChainCalls = $finder->find($oldStmt, function (Node $node) {
            return $node instanceof MethodCall;
        });
        $newMethodChainCalls = $finder->find($newStmt, function (Node $node) {
            return $node instanceof MethodCall;
        });

        if (empty($oldMethodChainCalls) || empty($newMethodChainCalls)) {
            return null;
        }

        $oldChain = array_map(function (MethodCall $call) {
            return $this->nodeToString($call->name);
        }, $oldMethodChainCalls);

        $newChain = array_map(function (MethodCall $call) {
            return $this->nodeToString($call->name);
        }, $newMethodChainCalls);

        if (array_slice($newChain, 0, count($oldChain)) === $oldChain && count($newChain) > count($oldChain)) {
            $added = array_slice($newChain, count($oldChain));
            return [
                'type' => TypeEnums::CHAIN_EXT,
                'old_chain' => $oldChain,
                'new_chain' => $newChain,
                'count' => 1,
                'data' => ['locations' => [$className . '::' . $methodName . 'extended with (' . implode(', ', $added) . ')']]
            ];
        }
        return null;
    }
}
