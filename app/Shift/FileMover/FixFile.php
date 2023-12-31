<?php

declare(strict_types=1);

namespace App\Shift\FileMover;

use App\Shift\Objects\FileClass;
use App\Shift\Shifter\PackageUpdates;
use Exception;

class FixFile
{
    public function __construct(private readonly FileClass $class)
    {
    }

    public function fix(): void
    {
        echo 'I am fixing '.$this->class->className.PHP_EOL;
        if ($this->class->className !== null) {
            $classMethods = array_filter($this->class->availableMethods(), fn ($method): bool => $method->className === $this->class->className);
            foreach ($classMethods as $classMethod) {
                try {
                    (new FixMethod($this->class))->fixMethod($classMethod);
                } catch (Exception $e) {
                    echo $e->getMessage().PHP_EOL;
                }
            }
        } else {
            (new FixMethod($this->class))->fix();
        }

        $this->replaceImports();

    }

    private function replaceImports(): void
    {
        $updates = PackageUpdates::methodChanges();
        foreach ($this->class->uses as $alias => $package) {
            if (! isset($updates[$package])) {
                continue;
            }

            if (! isset($updates[$package]['replaceWith'])) {
                continue;
            }

            $this->class->fileContents = str_replace(
                'use '.$this->constructImportString($package, $alias).';',
                'use '.$this->constructImportString($updates[$package]['replaceWith'], $alias).';',
                $this->class->fileContents
            );
        }

        file_put_contents($this->class->fileLocation, $this->class->fileContents);
    }

    private function constructImportString(string $package, string $alias): string
    {
        $splitClassName = explode('\\', $package);

        return $package.(end($splitClassName) === $alias ? '' : ' as '.$alias);
    }
}
