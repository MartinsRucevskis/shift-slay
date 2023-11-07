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

    public function __construct(\PhpParser\Node\Stmt\ClassMethod $method, string $className, string $fileContents)
    {
        $this->className = $className;
        $this->name = $method->name->name;
        foreach ($method->params as $param) {
            $this->params[] = new MethodParam($param);
        }
        $this->startLine = $method->getStartLine();
        $this->endLine = $method->getEndLine();
        $this->visibility = $this->visibility($method);
        $this->returnType = $method->getReturnType()->name ?? $this->methodReturnFromDocs($fileContents);
    }

    private function methodReturnFromDocs(string $file): string
    {
        if (preg_match('#^\h*/\*\*(?:\R\h*\*.*)*\R\h*\*/\R(?=.*\bfunction '.preg_quote($this->name).'\b)#m', $file, $matches) === 1) {
            preg_match('/@return\s+?(.+)\n/m', $matches[0], $matches);
        }
        if (isset($matches[1]) && $matches[1][0] === '\\') {
            $matches[1] = mb_substr($matches[1], 1);
        }

        return $matches[1] ?? 'void';
    }

    private function visibility(Property|\PhpParser\Node\Stmt\ClassMethod $instance): string
    {
        $visibility = 'private';
        if ($instance->isPublic()) {
            $visibility = 'public';
        } elseif ($instance->isProtected()) {
            $visibility = 'protected';
        }

        return $visibility;
    }
}
