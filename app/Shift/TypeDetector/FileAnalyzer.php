<?php

declare(strict_types=1);

namespace App\Shift\TypeDetector;

class FileAnalyzer
{
    public function __construct(private readonly string $fileContents)
    {
    }

    /**
     * @return array<string, string>
     */
    public function useStatements(): array
    {
        preg_match_all('/^use (.*?)( as (.*))?;$/m', $this->fileContents, $imports, PREG_SET_ORDER);
        $uses = [];
        foreach ($imports as $import) {
            $className = $import[1];
            $splitClassName = explode('\\', $className);
            $alias = $import[3] ?? end($splitClassName);
            $uses[$alias] = $className;
        }

        return $uses;
    }
}
