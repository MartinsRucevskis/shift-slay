<?php

namespace App\Shift\Objects;
use App\Shift\Enums\VisibilityEnum;
use PhpParser\Error;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Property;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;
use PhpParser\Node;
use PhpParser\Node\Stmt;
use PhpParser\NodeVisitor\ParentConnectingVisitor;
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter;

class FileClass
{
    public ?string $namespace;
    public ?string $className = null;
    public ?self $parent = null;
    public ?array $uses;
    public string $fileContents;
    public string $fileLocation ='';
    /**
     * @var \App\Shift\Objects\ClassMethod[]|null
     */
    public array $methods = [];
    /**
     * @var ClassVariable[]|null
     */
    public array $properties = [];

    /**
     * @throws \Exception
     */
    public function __construct(string $file)
    {
        $this->fileLocation = $file;
        $this->fileContents = file_get_contents($file);
        $classname = $this->className($file);
        $this->namespace = $this->namespace();
        $this->uses = $this->useStatements();
        if (isset($classname)) {
            $this->className = $classname;
            $this->analyzeClass($this->fileContents);
            try {
                if ($this->hasParentClass()) {
                    preg_match('/[class|trait|interface] [A-Za-z]* (extends|implements) ([a-zA-Z\\\\]*)/', $this->fileContents, $matches);
                    $classNames = str_contains($matches[2], '\\') ? $matches[2] : $this->uses[$matches[2]] ?? $this->namespace . '\\' . $matches[2];
                    $this->parent = new self($this->classMap()[$classNames]);
                }
            } catch (\Exception $e) {
                throw new \Exception($e->getMessage());
            }
        }
    }

    public function availableMethods(bool $includePrivate = true): array
    {
        $availableMethods = $this->methods;
        if (!$includePrivate) {
            $availableMethods = array_filter($availableMethods, function (\App\Shift\Objects\ClassMethod $availableMethod) {
                return $availableMethod->visibility !== VisibilityEnum::PRIVATE;
            });
        }
        if (isset($this->parent)) {
            $availableMethods = array_merge($availableMethods, $this->parent->availableMethods(false));
        }
        return $availableMethods;
    }

    public function availableVariables(bool $includePrivate)
    {
        $availableVariables = $this->properties;
        if (!$includePrivate) {
            $availableVariables = array_filter($availableVariables, function (ClassVariable $availableVariable) {
                return $availableVariable->visibility !== VisibilityEnum::PRIVATE;
            });
        }
        if (isset($this->parent)) {
            $availableVariables = array_merge($availableVariables, $this->parent->availableVariables(false));
        }
        return $availableVariables;
    }

    public function variableType(string $variableName): string{
        $type = array_filter($this->availableVariables(true), function ($variable) use ($variableName){
            return $variable->name === $variableName;
        });
        return $type[0]?->type ?? '';
    }

    public function methodReturnType(string $methodName): string{
        $method = array_reverse(array_filter($this->availableMethods(), function ($method) use ($methodName){
            return $method->name === $methodName;
        }));

        return array_pop($method)->returnType ?? '';
    }


    private function useStatements(): array
    {
        preg_match_all('/^use (.*?)( as (.*))?;$/m', $this->fileContents, $usedClasses, PREG_SET_ORDER);
        $uses = [];
        foreach ($usedClasses as $match) {
            $className = $match[1];
            $splitClassName = explode('\\', $className);
            $alias = $match[3] ?? end($splitClassName);
            $uses[$alias] = $className;
        }
        return $uses;
    }


    private function className(string $file): ?string
    {
        preg_match('/(class|interface) (.+?)[ |\s+|{]/ms', $this->fileContents, $className);
        $nameSpace = $this->namespace();
        if (isset($className[2])) {
            return isset($nameSpace)
                ? $nameSpace . '\\' . $className[2]
                : $className[2];
        };
        throw new \Exception('This ain\'t a class or its something wierd >:(, i ain\'t no magician(For now)! Fix it yourself ' . $file . PHP_EOL);
    }

    private function hasParentClass(): bool
    {
        return preg_match('/class [A-Za-z]* (extends|implements) ([a-zA-Z]*)/', $this->fileContents, $matches);
    }

    private function namespace(): ?string
    {
        preg_match('/.+?namespace (.+?);/ms', $this->fileContents, $nameSpace);
        return $nameSpace[1] ?? null;
    }

    private function classMap(): array
    {
        return require config('shift.composer_path');
    }

    public function analyzeClass(string $fileContents)
    {
        $parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP7);
        $nodes = $parser->parse($fileContents)[0];
        $stmts = $nodes->stmts ?? [];
        foreach ($stmts as $node) {
            if ($node instanceof Class_) {
                foreach ($node->stmts as $stmt) {
                    if ($stmt instanceof Property) {
                        $this->properties[] = new ClassVariable($stmt, $this->className, $this->fileContents);
                    }
                    if ($stmt instanceof ClassMethod) {
                        $this->methods[] = new \App\Shift\Objects\ClassMethod($stmt, $this->className);
                    }
                }
            }
        }

    }
}
