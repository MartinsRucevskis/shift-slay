<?php

namespace App\Shift;

use App\Shift\Enums\TypeEnums;
use PhpParser\Node\Stmt\ClassMethod;

class MethodSignatureChangeDetector
{
    use ClassResolverTrait;
    use MyCoolHelpers;

    public function detectSignatureChanges(ClassMethod $oldMethod, ClassMethod $newMethod, string $className, string $methodName): ?array
    {
        $changes = [];

        $oldParams = $oldMethod->params;
        $newParams = $newMethod->params;
        $maxParams = min(count($oldParams), count($newParams));
        for ($i = 0; $i < $maxParams; $i++) {

            $oldParamName = is_string($oldParams[$i]->var->name)
                ? $oldParams[$i]->var->name
                : $this->nodeToString($oldParams[$i]->var);

            $newParamName = is_string($newParams[$i]->var->name)
                ? $newParams[$i]->var->name
                : $this->nodeToString($newParams[$i]->var);

            $oldType = $oldParams[$i]->type
                ? $this->nodeToString($oldParams[$i]->type)
                : null;

            $newType = $newParams[$i]->type
                ? $this->nodeToString($newParams[$i]->type)
                : null;

            if ($oldParamName !== $newParamName) {
                $changes[] = [
                    'type' => TypeEnums::SIGNATURE_CHANGE,
                    'change' => 'parameter_rename',
                    'parameter' => $i,
                    'method' => $methodName,
                    'class' => $className,
                    'old_name' => $oldParamName,
                    'new_name' => $newParamName,
                    'count' => 1,
                    'data' => ['locations' => [$className . '::' . $methodName . 'parameter[' . $i . ']' . 'renaming']]
                ];
            }
            if ($oldType !== $newType) {
                $changes[] = [
                    'type' => TypeEnums::SIGNATURE_CHANGE,
                    'change' => 'parameter_type',
                    'parameter' => $i,
                    'method' => $methodName,
                    'class' => $className,
                    'old_type' => $oldType,
                    'new_type' => $newType,
                    'count' => 1,
                    'data' => ['locations' => [$className . '::' . $methodName . 'parameter[' . $i . '] type changed']]
                ];
            }
        }

        if (count($oldParams) !== count($newParams)) {
            $changes[] = [
                'type' => TypeEnums::SIGNATURE_CHANGE,
                'change' => 'parameter_count',
                'class' => $className,
                'method' => $methodName,
                'old_count' => count($oldParams),
                'new_count' => count($newParams),
                'count' => 1,
                'data' => ['locations' => [$className . '::' . $methodName . ' parameter count changed']]
            ];
        }

        $oldRet = $oldMethod->getReturnType();
        $newRet = $newMethod->getReturnType();

        $oldRetSting = $oldRet ? $this->nodeToString($oldRet) : null;
        $newRetString = $newRet ? $this->nodeToString($newRet) : null;

        if ($oldRetSting !== $newRetString) {
            $changes[] = [
                'type' => TypeEnums::SIGNATURE_CHANGE,
                'change' => 'return_type',
                'class' => $className,
                'method' => $methodName,
                'old_type' => $oldRetSting,
                'new_type' => $newRetString,
                'count' => 1,
                'data' => ['locations' => [$className . '::' . $methodName . ' return type changed']]
            ];
        }

        return empty($changes) ? null : $changes;
    }
}
