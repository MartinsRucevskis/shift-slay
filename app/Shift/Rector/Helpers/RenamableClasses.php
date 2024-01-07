<?php

namespace App\Shift\Rector\Helpers;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Collection;

class RenamableClasses
{
    public function __construct(private readonly string $directory)
    {
    }

    public function namespaceRenames(): array
    {
        $namespaceRenames = [];
        $files = (new Filesystem())->allFiles($this->directory);
        foreach ($files as $file) {
            $fileContents = $file->getContents();
            if ($this->isClass($fileContents)) {
                preg_match('/(class|trait|interface) (.*?)\s+/ms', $fileContents, $className);
                preg_match('/namespace (.*?);/ms', $fileContents, $namespace);
                $fullyQualifiedClassName = isset($namespace[1]) ? str_replace('/', '\\', $namespace[1]).'\\'.$className[2] : $className[2];
                $pathFromRoot = collect(explode('\\', str_replace(env('SHIFT_PROJECT_PATH'), '', $file->getPath())));
                $pathFromRoot = $pathFromRoot
                    ->filter(function ($path) {
                        return ! empty($path);
                    })
                    ->map(function ($path) {
                        return ucfirst(str_replace('.php', '', $path));
                    })
                    ->values();
                /** @var Collection $pathFromRoot */
                $pathFromRoot = $pathFromRoot->map(function ($path) {
                    return ucfirst($path);
                });
                $pathFromRoot = $pathFromRoot->implode('\\');
                /** @var string $pathFromRoot */
                $pathFromRoot = ltrim($pathFromRoot, '\\');
                $newClassName = $pathFromRoot.'\\'.$className[2];
                if ($fullyQualifiedClassName !== $newClassName) {
                    $namespaceRenames[$fullyQualifiedClassName] = $newClassName;
                }
            }
        }

        return $namespaceRenames;
    }

    private function isClass(string $file): bool
    {
        $tokens = token_get_all($file);
        foreach ($tokens as $token) {
            if (is_array($token) && in_array($token[0], [T_CLASS, T_INTERFACE, T_TRAIT])) {
                return true;
            }
        }

        return false;
    }
}
