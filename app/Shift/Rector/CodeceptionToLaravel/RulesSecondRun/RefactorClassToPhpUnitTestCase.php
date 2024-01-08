<?php

namespace App\Shift\Rector\CodeceptionToLaravel\RulesSecondRun;

use PhpParser\Node;
use PhpParser\Node\Name;
use Rector\Core\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

class RefactorClassToPhpUnitTestCase extends AbstractRector
{
    public function getNodeTypes(): array
    {
        return [Node\Stmt\Class_::class];
    }

    public function refactor(Node $node): ?Node
    {
        if (str_ends_with($node->name->name, 'Cest')) {
            $node->name->name = str_replace('Cest', 'Test', $node->name->name);
            $node->extends = new Name('Tests\TestCase');
        } elseif (isset($node->extends) && $this->isObjectType($node->extends, new \PHPStan\Type\ObjectType('Codeception\Test\Unit'))) {
            $node->extends = new Name('Tests\TestCase');
        }

        return $node;
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Correct test class from Codeception', [
            new CodeSample(
                'class RandomCest {}',
                'class RandomTest extends Tests\TestCase {}'
            ),
        ]);
    }
}
