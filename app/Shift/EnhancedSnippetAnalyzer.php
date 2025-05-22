<?php

namespace App\Shift;

use App\Shift\Enums\TypeEnums;
use PhpParser\Node;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\LNumber;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Interface_;
use PhpParser\NodeFinder;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\ParserFactory;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;


class EnhancedSnippetAnalyzer
{
    use ClassResolverTrait;
    use MyCoolHelpers;

    private string $dirOld;
    private string $dirNew;
    private array $changes = [];
    /** @var RefactorDetectorInterface[] */
    private array $detectors;
    /** @var MethodSignatureChangeDetector */
    private MethodSignatureChangeDetector $signatureDetector;

    private int|float $parentWeight;
    private int|float $childWeight;

    /**
     * Threshold above which two nodes are considered for alignment.
     *
     * @var float
     */
    private float $matchThreshold;

    public function __construct(
        string $oldVersionDirectory,
        string $newVersionDirectory,
        array  $detectors = [],
        float  $parentWeight = 1,
        float  $childWeight = 2,
        float  $matchThreshold = 0.5
    )
    {
        $this->dirOld = $oldVersionDirectory;
        $this->dirNew = $newVersionDirectory;
        $this->detectors = $detectors ?: [
            new EnhancedConsantChangeDetector(),
            new MethodCallChainExtensionDetector()
        ];
        $this->signatureDetector = new MethodSignatureChangeDetector();
        $sum = $parentWeight + $childWeight;
        $this->parentWeight = $parentWeight / $sum;
        $this->childWeight = $childWeight / $sum;
        $this->matchThreshold = $matchThreshold;
    }

    public function analyze(string $jsonOutputPath): void
    {

//        (new DiffBenchmark($this))->run();
//        return;
        $oldAst = $this->parseDirectory($this->dirOld);
        $newAst = $this->parseDirectory($this->dirNew);
        $classesOld = $this->collectClasses($oldAst);
        $classesNew = $this->collectClasses($newAst);
        $commonClassNames = array_intersect_key($classesOld, $classesNew);
        foreach ($commonClassNames as $name => $oldCls) {
            $newCls = $classesNew[$name];
            $this->compareClasses($name, $oldCls, $newCls);
        }

//        return;
        $groupedChanges = [];
        foreach ($this->changes as $change) {
            $locations = $change['data']['locations'] ?? [];
            $className = 'unknown';
            if (!empty($locations)) {
                $parts = explode('::', $locations[0]);
                $className = $parts[0] ?? 'unknown';
            }
            $groupedChanges[$className][] = $change;
        }
        file_put_contents($jsonOutputPath, json_encode($groupedChanges, JSON_PRETTY_PRINT));
        $i = 1;
        foreach ($groupedChanges as $class => $changes) {
            echo 'Class: ' . $class . PHP_EOL;
            foreach ($changes as $change) {
                $type = $change['type'] ?? '';
                $count = $change['count'] ?? 0;
                echo '['.$i.']'. $type . '(count '. $count.')' . PHP_EOL;
                $i++;
            }
            echo PHP_EOL;
        }
    }

    private function parseDirectory(string $dir): array
    {
        $files = $this->getPhpFiles($dir);
        $parser = (new ParserFactory())->create(ParserFactory::PREFER_PHP7);
        $combined = [];
        foreach ($files as $f) {
            $code = file_get_contents($f);
            $ast = $parser->parse($code);
            $traverser = new NodeTraverser();
            $traverser->addVisitor(new NameResolver(null, ['replaceNodes' => true]));
            $ast = $traverser->traverse($ast);
            $combined = array_merge($combined, $ast);
        }

        return $combined;
    }

    private function getPhpFiles(string $dir): array
    {
        $result = [];
        $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
        foreach ($it as $file) {
            if ($file->isDir() || $file->getExtension() !== 'php') {
                continue;
            }
            $result[] = $file->getRealPath();
        }
        return $result;
    }

    /**
     * @param Node[] $ast
     * @return array<class-string, Class_>
     */
    private function collectClasses(array $ast): array
    {
        $finder = new NodeFinder();
        $classes = $finder->findInstanceOf($ast, Class_::class);
        $classes = array_merge($classes, $finder->findInstanceOf($ast, Interface_::class));
        $map = [];
        foreach ($classes as $class) {
            $className = $class->namespacedName ? $class->namespacedName->toString() : ($class->name ? $class->name->toString() : '__anon__');
            $map[$className] = $class;
        }
        return $map;
    }

    private function compareClasses(string $className, Class_|Interface_ $oldCls, Class_|Interface_ $newCls): void
    {
        $oldMethods = $this->collectMethods($oldCls);
        $newMethods = $this->collectMethods($newCls);
        $matchedMethods = array_intersect_key($oldMethods, $newMethods);
        foreach ($matchedMethods as $mName => $oldMethod) {
            $newMethod = $newMethods[$mName];
            $sigChanges = $this->signatureDetector->detectSignatureChanges($oldMethod, $newMethod, $className, $mName);
            if ($sigChanges) {
                foreach ($sigChanges as $sigChange) {
                    $this->recordChange($sigChange);
                }
            }
        }
        foreach ($matchedMethods as $mName => $oldMethod) {
            $newMethod = $newMethods[$mName];
            $this->compareMethodBodies($className, $mName, $oldMethod, $newMethod);
        }
    }

    private function compareMethodBodies(string $className, string $methodName, ClassMethod $oldMethod, ClassMethod $newMethod): void
    {
        $oldStmts = $oldMethod->stmts ?? [];
        $newStmts = $newMethod->stmts ?? [];
        $pairs = $this->alignStatements($oldStmts, $newStmts);
//        $this->debugPairs($className, $methodName, $oldStmts, $newStmts, $pairs);


        foreach ($pairs as [$oldStmt, $newStmt]) {
            foreach ($this->detectors as $detector) {
                $change = $detector->detect($className, $methodName, $oldStmt, $newStmt);
                if ($change) {
                    if (isset($change['multiple'])) {
                        foreach ($change['multiple'] as $ch) {
                            $this->recordChange($ch);
                        }
                    } else {
                        $this->recordChange($change);
                    }
                }
            }
        }
    }

    private function recordChange(array $change): void
    {
        foreach ($this->changes as &$existingChange) {
            if ($this->entryEquals($existingChange, $change)) {
                $increment = $change['count'] ?? 1;
                $existingChange['count'] += $increment;

                if (isset($existingChange['data']['locations'], $change['data']['locations'])) {
                    $existingChange['data']['locations'] = array_merge(
                        $existingChange['data']['locations'],
                        $change['data']['locations']
                    );
                }
                return;
            }
        }

        $this->changes[] = $change;
    }

    private function entryEquals(array $a, array $b): bool
    {
        $typeA = $a['type'] ?? '';
        $typeB = $b['type'] ?? '';

        if ($typeA !== $typeB) {
            return false;
        }

        switch ($typeA) {
            case TypeEnums::REF_CHANGE:
                return (($a['old_class'] ?? '') === ($b['old_class'] ?? '')) &&
                    (($a['old_name'] ?? '') === ($b['old_name'] ?? '')) &&
                    (($a['new_class'] ?? '') === ($b['new_class'] ?? '')) &&
                    (($a['new_name'] ?? '') === ($b['new_name'] ?? ''));

            case TypeEnums::CHAIN_EXT:
                return (($a['old_chain'] ?? []) === ($b['old_chain'] ?? [])) &&
                    (($a['new_chain'] ?? []) === ($b['new_chain'] ?? []));

            case TypeEnums::SIGNATURE_CHANGE:
                if (($a['change'] ?? '') !== ($b['change'] ?? '')) {
                    return false;
                }
                if ($a['change'] == 'return_type') {
                    return $a['method'] === $b['method']
                        && $a['old_type'] === $b['old_type']
                        && $a['new_type'] === $b['new_type']
                        && $a['old_type'] === $b['old_type']
                        && $a['class'] === $b['class'];
                }
                if ($a['change'] == 'parameter_type') {
                    return $a['method'] === $b['method']
                        && $a['class'] === $b['class']
                        && $a['old_type'] === $b['old_type']
                        && $a['new_type'] === $b['new_type']
                        && $a['parameter'] === $b['parameter'];
                }
                if (in_array($a['change'], ['parameter_rename', 'parameter_type'], true)) {
                    return ($a['parameter'] ?? null) === ($b['parameter'] ?? null);
                }
                return true;

            default:
                return false;
        }
    }

    public function initMatrix(int $oldCount, int $newCount): array
    {
        $dp = [];
        for ($i = 0; $i <= $oldCount; $i++) {
            $dp[$i] = [];
            for ($j = 0; $j <= $newCount; $j++) {
                $dp[$i][$j] = ['score' => 0];
            }
        }

        return $dp;
    }

    /**
     * @param Node[] $oldStatements
     * @param Node[] $newStatements
     * @return array
     */
    public function alignStatements(array $oldStatements, array $newStatements): array
    {
        $oldCount = count($oldStatements);
        $newCount = count($newStatements);
        $dp = $this->initMatrix($oldCount, $newCount);

        for ($i = 1; $i <= $oldCount; $i++) {
            for ($j = 1; $j <= $newCount; $j++) {
                $similarity = $this->computeTreeSimilarity($oldStatements[$i - 1], $newStatements[$j - 1]);
                $skipA = $dp[$i - 1][$j];
                $skipB = $dp[$i][$j - 1];
                $best = ($skipA['score'] > $skipB['score']) ? $skipA : $skipB;

                if ($similarity >= $this->matchThreshold) {
                    $score = $dp[$i - 1][$j - 1]['score'] + $similarity;
                    if ($score > $best['score']) {
                        $dp[$i][$j] = ['score' => $score];
                        continue;
                    }
                }

                $dp[$i][$j] = $best;
            }
        }
        $aligned = [];
        $i = $oldCount;
        $j = $newCount;
        while ($i > 0 && $j > 0) {
            $similarity = $this->computeTreeSimilarity($oldStatements[$i - 1], $newStatements[$j - 1]);
            $potential = $dp[$i - 1][$j - 1]['score'] + $similarity;
            if ($similarity >= $this->matchThreshold && abs($dp[$i][$j]['score'] - $potential) < 1e-6) {
                $aligned[] = [$oldStatements[$i - 1], $newStatements[$j - 1]];
                $i--;
                $j--;
            } else {
                if ($dp[$i - 1][$j]['score'] > $dp[$i][$j - 1]['score']) {
                    $i--;
                } else {
                    $j--;
                }
            }
        }

        return array_reverse($aligned);
    }

    public function computeTreeSimilarity(Node $oldNode, Node $newNode): float
    {
        if ($this->nodeTypesDiffer($oldNode, $newNode)) {
            return 0.0;
        }

        if ($this->isLeafNode($oldNode)) {
            return $this->computeLeafSimilarity($oldNode, $newNode);
        }

        $childrenOld = $this->extractChildNodes($oldNode);
        $childrenNew = $this->extractChildNodes($newNode);

        if (empty($childrenOld) && empty($childrenNew)) {
            return $this->stringSimilarity(
                $this->nodeToString($oldNode),
                $this->nodeToString($newNode)
            );
        }

        $childScore = $this->normalizedChildrenSimilarity($childrenOld, $childrenNew);

        return $this->parentWeight + $this->childWeight * $childScore;
    }

    /**
     * @param Node[] $oldKids
     * @param Node[] $newKids
     */
    private function normalizedChildrenSimilarity(array $oldKids, array $newKids): float
    {
        $countA = count($oldKids);
        $countB = count($newKids);
        $similarityMatrix = array_fill(
            0,
            $countA + 1,
            array_fill(0, $countB + 1, 0.0)
        );


        for ($i = 1; $i <= $countA; $i++) {
            for ($j = 1; $j <= $countB; $j++) {
                $matchScore = $this->computeTreeSimilarity(
                    $oldKids[$i - 1],
                    $newKids[$j - 1]
                );
                $similarityMatrix[$i][$j] = max(
                    $similarityMatrix[$i - 1][$j],
                    $similarityMatrix[$i][$j - 1],
                    $similarityMatrix[$i - 1][$j - 1] + $matchScore
                );
            }
        }

        $bestTotal = $similarityMatrix[$countA][$countB];
        return $countA || $countB
            ? $bestTotal / max($countA, $countB)
            : 0.0;
    }

    private function isLeafNode(Node $node): bool
    {
        return $node instanceof Identifier
            || $node instanceof Name
            || $node instanceof Variable
            || $node instanceof String_
            || $node instanceof LNumber;
    }


    public function computeLeafSimilarity(Node $node1, Node $node2): float
    {
        switch (true) {
            case $node1 instanceof Identifier && $node2 instanceof Identifier:
            case $node1 instanceof Name && $node2 instanceof Name:
                return $this->stringSimilarity($node1->toString(), $node2->toString());

            case $node1 instanceof Variable && $node2 instanceof Variable:
                $val1 = is_string($node1->name) ? '$' . $node1->name : $this->nodeToString($node1->name);
                $val2 = is_string($node2->name) ? '$' . $node2->name : $this->nodeToString($node2->name);
                return $this->stringSimilarity($val1, $val2);

            case $node1 instanceof String_ && $node2 instanceof String_:
                return $this->stringSimilarity($node1->value, $node2->value);

            case $node1 instanceof LNumber && $node2 instanceof LNumber:
                $val1 = $node1->value;
                $val2 = $node2->value;
                return 1.0 - (abs($val1 - $val2) / max(max(abs($val1), abs($val2)), 1));

            default:
                return 0.0;
        }
    }

    private function stringSimilarity(string $a, string $b): float
    {
        $aLower = mb_strtolower($a);
        $bLower = mb_strtolower($b);

        if ($aLower === $bLower) {
            return 1.0;
        }

        $lev = levenshtein($aLower, $bLower);
        $maxLen = max(mb_strlen($aLower), mb_strlen($bLower));
        if ($maxLen === 0) {
            return 1.0;
        }

        return 1.0 - ($lev / $maxLen);
    }

    private function nodeTypesDiffer(Node $node1, Node $node2): bool
    {
        return get_class($node1) !== get_class($node2);
    }

    private function extractChildNodes(Node $node): array
    {
        $children = [];
        foreach ($node->getSubNodeNames() as $subName) {
            $value = $node->$subName;
            if (is_array($value)) {
                foreach ($value as $element) {
                    if ($element instanceof Node) {
                        $children[] = $element;
                    }
                }
            } elseif ($value instanceof Node) {
                $children[] = $value;
            }
        }
        return $children;
    }

    private function collectMethods(Class_|Interface_ $class): array
    {
        $methods = [];
        foreach ($class->getMethods() as $method) {
            $methods[$method->name->toString()] = $method;
        }
        return $methods;
    }
}
