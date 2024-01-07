<?php

namespace App\Shift\Rector\CodeceptionToLaravel\RulesFirstRun;

use PhpParser\Node;
use PhpParser\Node\Stmt\ClassMethod;
use Rector\Core\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

class AddTestAttributeForTests extends AbstractRector
{
    public function getNodeTypes(): array
    {
        return [Node\Stmt\ClassMethod::class];
    }

    /** @param  Node\Stmt\ClassMethod  $node */
    public function refactor(Node $node): ?Node
    {
        if ($this->hasTestAttribute($node)) {
            return null;
        }
        if (str_ends_with($this->file->getFilePath(), 'Cest.php') && $node->isPublic()) {
            $node->attrGroups = array_merge([new Node\AttributeGroup([
                new Node\Attribute(new Node\Name('PHPUnit\Framework\Attributes\Test')),
            ])], $node->attrGroups ?? []);
        }

        return $node;
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Add test param to public methods in files that end with Cest.php', [
            new CodeSample(

                'public function whenSomethingThenAssertIt(): void {
                     $this->getJson(\'endpoint\')
                        ->assertOk();
                 }',

                '
                #[Test]
                public function whenSomethingThenAssertIt(): void {
                     $this->getJson(\'endpoint\')
                        ->assertOk();
                 }',
            ),
        ]);
    }

    private function hasTestAttribute(ClassMethod $node): bool
    {
        foreach ($node->attrGroups as $attrGroup) {
            foreach ($attrGroup->attrs as $attr) {
                if (in_array($this->getName($attr->name), ['Test', 'PHPUnit\Framework\Attributes\Test'])) {
                    return true;
                }
            }
        }

        return false;
    }
}
