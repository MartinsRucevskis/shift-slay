<?php

namespace App\Shift\Objects;
use App\Shift\Enums\VisibilityEnum;
use App\Shift\TypeDetector\FileAnalyzer;
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
        $this->uses = (new FileAnalyzer($this->fileContents))->useStatements();
        // TODO: Move this to function
        if (isset($classname)) {
            $this->className = $classname;
            $this->analyzeClass($this->fileContents);
            try {
                if ($this->hasParentClass()) {
                    $this->parent = new self($this->classMap()[$this->namespacedParentClass()]);
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

    public function availableVariables(bool $includePrivate): ?array
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

    private function className(string $file): ?string
    {
        preg_match('/(class|interface) (.+?)[ |\s+|{]/ms', $this->fileContents, $className);
        $nameSpace = $this->namespace();
        if (isset($className[2])) {
            return isset($nameSpace)
                ? $nameSpace . '\\' . $className[2]
                : $className[2];
        };
        return '';
    }

    private function hasParentClass(): bool
    {
        return preg_match('/class [A-Za-z]* (extends|implements) ([a-zA-Z]*)/', $this->fileContents, $matches);
    }

    private function namespacedParentClass(): string{
        preg_match('/[class|trait|interface] [A-Za-z]* (extends|implements) ([a-zA-Z\\\\]*)/', $this->fileContents, $matches);
        $parentClass = $matches[2];
        return str_contains($parentClass, '\\')
            ? $parentClass
            : $this->uses[$parentClass] ?? $this->namespace . '\\' . $parentClass;
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

    public function analyzeClass(string $fileContents): void
    {
        $parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP7);
        $nodes = $parser->parse($fileContents)[0];
        $stmts = $nodes->stmts ?? [];
        foreach ($stmts as $node) {
            if ($node instanceof Class_ || $node instanceof Stmt\Interface_) {
                $this->analyzeNode($node);
            }
        }
    }

    private function analyzeNode($node): void
    {
        foreach ($node->stmts as $stmt) {
            if ($stmt instanceof Property) {
                $this->analyzeProperty($stmt);
            }
            if ($stmt instanceof ClassMethod) {
                $this->analyzeMethod($stmt);
            }
        }
    }

    private function analyzeProperty(Property $property): void
    {
        $this->properties[] = new ClassVariable($property, $this->className, $this->fileContents);
    }

    private function analyzeMethod(ClassMethod $method): void
    {
        $this->methods[] = new \App\Shift\Objects\ClassMethod($method, $this->className, $this->fileContents);
    }

}