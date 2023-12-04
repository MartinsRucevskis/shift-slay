<?php

namespace App\Shift\Rector\CodeceptionToLaravel\RulesFirstRun;

use PhpParser\Comment\Doc;
use PhpParser\Node;
use PhpParser\Node\Expr\Variable;
use Rector\Core\PhpParser\Node\BetterNodeFinder;
use Rector\Core\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

class ExampleToTestWithDocs extends AbstractRector
{
    public function __construct(private BetterNodeFinder $lowaks)
    {
    }

    private string $exampleName = '';

    public function getNodeTypes(): array
    {
        return [Node\Stmt\ClassMethod::class];
    }

    public function refactor(Node $node): ?Node
    {
        $docComment = $node->getDocComment();
        if (! isset($docComment)) {
            return null;
        }
        preg_match_all('#@example\s+?([\[|\{].+?[\]|\}])\s#ms', $docComment->getText(), $matches);
        $node->setDocComment(new Doc(preg_replace('#\s+?\*\s+?@example\s+?([\[|\{].+?[\]|\}])\s#ms', '', $docComment->getText())));
        $customKeys = [];
        if (empty($matches[1])) {
            return null;
        }
        foreach ($matches[1] as $tag) {
            $array = json_decode($tag, true);
            $arrayWithCustomKeys = empty(array_filter(array_keys($array), function ($item) {
                return preg_match('/[0-9]/ms', $item, $matchesss) === 1;
            }));
            if ($arrayWithCustomKeys) {
                $customKeys = array_merge($customKeys, array_keys($array));
                $array = array_values($array);
            }
            $args = $this->nodeFactory->createArg($array);
            $node->attrGroups[] = new Node\AttributeGroup([
                new Node\Attribute(new Node\Name('PHPUnit\Framework\Attributes\TestWith'), [$args]),
            ]);
        }
        foreach ($node->params as $key => $param) {
            if ($this->getType($param)->getObjectClassNames()[0] === 'Codeception\Example') {
                $this->exampleName = $param->var->name;
                unset($node->params[$key]);
                $node->params = array_values($node->params);
            }
        }
        $paramCount = count($node->params);
        foreach ($node->attrGroups as $group) {
            $providerItems = $group->attrs[0]->args[0]->value->items;
            $i = $paramCount;
            foreach ($providerItems as $item) {
                if (! isset($this->params[$i])) {
                    $paramName = $arrayWithCustomKeys ? $customKeys[$i - $paramCount] : $i - $paramCount;
                    $node->params[$i] = new Node\Param(new Variable('argumentFromProvider'.$paramName), type: get_debug_type($item->value->value));
                }
                $i++;
            }
        }
        $this->traverseNodesWithCallable($node, function (Node $nodeStatement) {
            if ($nodeStatement instanceof Node\Expr\ArrayDimFetch && $nodeStatement->var->name === $this->exampleName) {
                return new Variable('argumentFromProvider'.$nodeStatement->dim->value);
            }

            return null;
        });

        return $node;
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
