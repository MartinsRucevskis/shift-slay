<?php


namespace App\Shift\Rector\CodeceptionToLaravel\Rules;

use _PHPStan_d147f4cc6\React\ChildProcess\Process;
use Illuminate\Database\Query\Expression;
use PhpParser\Comment\Doc;
use PhpParser\Node;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Use_;
use PhpParser\Node\Stmt\UseUse;
use PHPStan\PhpDocParser\Ast\Attribute;
use PHPStan\Type\ObjectType;
use Rector\Core\PhpParser\Node\BetterNodeFinder;
use Rector\Core\Rector\AbstractRector;
use Rector\NodeTypeResolver\Node\AttributeKey;
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
        if(!isset($docComment)){
            return null;
        }
        preg_match_all('#@example\s+?(\[.+?\])\s#ms', $docComment->getText(), $matches);
        $node->setDocComment(new Doc(preg_replace('#\s+?\*\s+?@example\s+?(\[.+?\])\s#ms', '', $docComment->getText())));
        foreach ($matches[1] as $tag) {
            $array = json_decode($tag, true);
            $args = $this->nodeFactory->createArg($array);
            $node->attrGroups[] = new Node\AttributeGroup([
                new Node\Attribute(new Node\Name('\PHPUnit\Framework\Attributes\TestWith'), [$args]),
            ]);
        }
        foreach ($node->params as $key => $param) {
            if ($this->getType($param)->getObjectClassNames()[0] === 'Codeception\Example') {
                $this->exampleName = $param->var->name;
                unset($node->params[$key]);
            }
        }
        foreach ($node->attrGroups as $group) {
            $providerItems = $group->attrs[0]->args[0]->value->items;
            $i = 1;
            foreach ($providerItems as $item) {
                $node->params[$i] = new Node\Param(new Variable('argumentFromProvider' . $i-1), type: get_debug_type($item->value->value));
                $i++;
            }
        }
        $this->traverseNodesWithCallable($node, function (Node $nodeStatement) use ($node) {
            if ($nodeStatement instanceof Node\Expr\ArrayDimFetch && $nodeStatement->var->name === $this->exampleName) {
                return  new Variable('argumentFromProvider' . $nodeStatement->dim->value);
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
