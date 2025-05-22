<?php

namespace App\Shift;

use App\Shift\Enums\TypeEnums;
use PhpParser\Node;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\NodeFinder;
use PHPStan\BetterReflection\Reflection\ReflectionClass as BetterReflectionClass;
use PHPStan\BetterReflection\Reflector\ClassReflector;
use PHPStan\BetterReflection\SourceLocator\Type\DirectoriesSourceLocator;

class EnhancedConsantChangeDetector implements RefactorDetectorInterface
{
    use ClassResolverTrait;

    public function detect(string $className, string $methodName, Node $oldStmt, Node $newStmt): ?array
    {
        $finder = new NodeFinder();
        $oldConsts = $finder->find($oldStmt, function (Node $n) {
            return $n instanceof ClassConstFetch;
        });
        $newConsts = $finder->find($newStmt, function (Node $n) {
            return $n instanceof ClassConstFetch;
        });

        $changes = [];
        $usedNewIndices = [];
        foreach ($oldConsts as $i => $oldConst) {
            $oldClasss = $this->resolveClass($oldConst->class);
            $oldName = $oldConst->name->toString();
            $bestMatch = null;
            $bestScore = 0.0;
            $bestIndex = null;
            foreach ($newConsts as $j => $newConst) {
                if (in_array($j, $usedNewIndices)) {
                    continue;
                }
                $newClass = $this->resolveClass($newConst->class);
                $newName = $newConst->name->toString();
                $score = $this->similarityScore($oldClasss, $oldName, $newClass, $newName);
                if ($score > $bestScore) {
                    $bestScore = $score;
                    $bestMatch = [$oldClasss, $oldName, $newClass, $newName];
                    $bestIndex = $j;
                }
            }
            if (isset($bestMatch[0], $bestMatch[1], $bestMatch[2], $bestMatch[3]) || $bestMatch) {
                $changes[] = [
                    'type' => TypeEnums::REF_CHANGE,
                    'old_class' => $bestMatch[0],
                    'old_name' => $bestMatch[1],
                    'new_class' => $bestMatch[2],
                    'new_name' => $bestMatch[3],
                    'count' => 1,
                    'data' => ['locations' => ["$className::$methodName constant {$bestMatch[1]}"]]
                ];
                $usedNewIndices[] = $bestIndex;
            }
        }
        foreach ($newConsts as $j => $newConst) {
            if (!in_array($j, $usedNewIndices)) {
                $newClass = $this->resolveClass($newConst->class);
                $newName = $newConst->name->toString();
                $changes[] = [
                    'type' => TypeEnums::REF_CHANGE,
                    'old_class' => '???',
                    'old_name' => '???',
                    'new_class' => $newClass,
                    'new_name' => $newName,
                    'count' => 1,
                    'data' => ['locations' => ["$className::$methodName new constant {$newName}"]]
                ];
            }
        }
        if (empty($changes)) {
            return null;
        }
        $aggregated = [];
        foreach ($changes as $change) {
            $key = implode('|', [
                $change['type'], $change['old_class'], $change['old_name'],
                $change['new_class'], $change['new_name']
            ]);
            if (isset($aggregated[$key])) {
                $aggregated[$key]['count'] += $change['count'];
                $aggregated[$key]['data']['locations'] = array_merge(
                    $aggregated[$key]['data']['locations'],
                    $change['data']['locations']
                );
            } else {
                $aggregated[$key] = $change;
            }
        }
        return ['multiple' => array_values($aggregated)];
    }

    private function similarityScore(string $oldClass, string $oldName, string $newClass, string $newName): float
    {
        return similar_text($oldClass, $newClass) + similar_text($oldName, $newName);
    }
}
