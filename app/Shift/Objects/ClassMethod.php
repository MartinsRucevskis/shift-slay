<?php

namespace App\Shift\Objects;

use PhpParser\Node\Stmt\Property;

class ClassMethod
{
    public string $visibility;
    public int $startLine;
    public int $endLine;
    public string $name;
    public string $className;
    public ?string $returnType;
    /**
     * @var MethodParam[]|null
     */
    public array $params = [];
    public function __construct(\PhpParser\Node\Stmt\ClassMethod $method, string $className)
    {
        $this->className = $className;
        $this->name = $method->name->name;
        foreach ($method->params as $param) {
            $this->params[] = new MethodParam($param);
        }
        $this->startLine = $method->getStartLine();
        $this->endLine = $method->getEndLine();
        $this->visibility = $this->visibility($method);
        $this->returnType = $method->getReturnType()->name ?? 'null';
    }

//    private function returnTypeFromDocBlock(){
//        preg_match($this->)
//    }

    private function visibility(Property|\PhpParser\Node\Stmt\ClassMethod $instance): string{
        $visibility = 'private';
        if ($instance->isPublic()) {
            $visibility = 'public';
        } elseif ($instance->isProtected()) {
            $visibility = 'protected';
        }

        return $visibility;
    }
}
