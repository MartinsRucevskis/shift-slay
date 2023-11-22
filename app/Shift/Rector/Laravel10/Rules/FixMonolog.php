<?php

namespace App\Shift\Rector\Laravel10\Rules;

use PhpParser\Node;
use PhpParser\Node\Identifier;
use PHPStan\Type\ObjectType;
use Rector\Core\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

class FixMonolog extends AbstractRector
{
    private array $monologPropertyNames = ['message', 'formatted', 'extra', 'context', 'level', 'channel', 'datetime'];

    /**
     * @return string[]
     */
    public function getNodeTypes(): array
    {
        return [Node\Stmt\Class_::class];
    }

    /**
     * @param Node\Stmt\Class_ $node
     */
    public function refactor(Node $node): ?Node
    {
        if ($this->nodeTypeResolver->isObjectType($node, new ObjectType('Monolog\Handler\AbstractProcessingHandler'))) {
            /* @var $method Node\Stmt\ClassMethod */
            foreach ($node->getMethods() as $method) {
                if (in_array($method->name->name, ['handle', 'write'])) {
                    foreach ($method->params as $param) {
                        $param->type = new Identifier('\Monolog\LogRecord');
                    }
                    $this->traverseNodesWithCallable((array)$method->stmts, function (Node $node): ?Node {
                        if ($node instanceof Node\Expr\ArrayDimFetch) {
                            $dim = $node->dim;
                            if ($dim instanceof Node\Scalar\String_) {
                                $dim = $dim->value;
                            }
                            if (in_array($dim, $this->monologPropertyNames)) {
                                return new Node\Expr\PropertyFetch($node->var, $dim);
                            }
                        }

                        return null;
                    });
                }
            }
        }
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
