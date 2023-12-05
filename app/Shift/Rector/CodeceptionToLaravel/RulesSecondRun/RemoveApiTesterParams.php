<?php

namespace App\Shift\Rector\CodeceptionToLaravel\RulesSecondRun;

use PhpParser\Builder\Param;
use PhpParser\Node;
use PhpParser\Node\Expr\Variable;
use PHPStan\Type\ObjectType;
use Rector\Core\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

// Will generate diff as (something)->chainedCall(), but actually will be converted to something->chainedCall(), due to afterTraverse regex modification
class RemoveApiTesterParams extends AbstractRector
{
    public function getNodeTypes(): array
    {
        return [Node\Stmt\ClassMethod::class];
    }

    /**
     * @param  Node\Stmt\ClassMethod  $node
     */
    public function refactor(Node $node): ?Node
    {
        $paramName = '';
        $params = $node->params;
        foreach ($params as $key => $param) {
            if ($this->isObjectType($param, new ObjectType('ApiTester'))) {
                if (str_ends_with($this->file->getFilePath(), 'Cest.php')) {
                    $this->traverseNodesWithCallable($node->stmts, function ($stmnt) use ($param) {
                        if ($stmnt instanceof Variable && $this->isName($stmnt, $this->getName($param))) {
                            return new Variable('this');
                        }
                    });
                    unset($params[$key]);
                } else {
                    $this->traverseNodesWithCallable($node->stmts, function ($stmnt) use ($param) {
                        if ($stmnt instanceof Variable && $this->isName($stmnt, $this->getName($param))) {
                            return new Variable('testCase');
                        }
                    });
                    $params[$key]->var->name = 'testCase';
                }
            }
        }
        $node->params = array_values($params);

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
