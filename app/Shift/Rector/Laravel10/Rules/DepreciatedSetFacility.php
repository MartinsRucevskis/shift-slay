<?php

namespace App\Shift\Rector\Laravel10\Rules;

use PhpParser\Node;
use PHPStan\Type\ObjectType;
use Rector\Core\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

class DepreciatedSetFacility extends AbstractRector
{
    public function getNodeTypes(): array
    {
        return [Node\Expr\MethodCall::class];
    }

    public function refactor(Node $node): ?Node
    {
        if (! $this->nodeTypeResolver->isMethodStaticCallOrClassMethodObjectType($node, new ObjectType('Gelf\\Message'))) {
            return null;
        }
        $methodCallName = $this->getName($node->name);
        if ($methodCallName !== 'setFacility') {
            return null;
        }
        $node->name = new Node\Identifier('setAdditional');

        $facilityArg = new Node\Arg(new Node\Scalar\String_('facility'));
        array_unshift($node->args, $facilityArg);

        return $node;
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Updates setFacility to be setAdditional(\'facility\')', [
            new CodeSample(

                '$gelf->setFacility($facility);',

                '$gelf->setAdditional(\'facility\', $facility)'
            ),
        ]);
    }
}
