<?php

declare(strict_types=1);

namespace App\Shift\FileMover;

use App\Shift\Objects\ClassMethod;
use App\Shift\Objects\FileClass;
use App\Shift\Shifter\CommonUpdates;
use App\Shift\Shifter\PackageUpdates;
use App\Shift\TokenTraverser\TokenTraverser;
use App\Shift\TypeDetector\TypeDetector;

class FixMethod
{
    private array $tokens;

    private TokenTraverser $tokenTraverse;

    /**
     * @var array[]
     */
    private array $availableVariables;

    public function __construct(private readonly FileClass $class)
    {

    }

    public function fixMethod(ClassMethod $method): void
    {
        $needReplacing[] = array_intersect_key(PackageUpdates::methodChanges(), array_flip($this->class->uses));

        $aliases = array_filter($this->class->uses, fn($use) => in_array($use, array_keys($needReplacing[0])));
        preg_match('/.*?function.*?\(.*?\):? ?(.*?)[\s+]?{(.*)}/ms', $this->methodBody($method), $matches);
        $this->tokens = $this->removePhpTags($this->chopTokens(token_get_all('<?php'.$matches[2].'?>')));
        $originalTokens = $this->tokens;
        $this->tokenTraverse = new TokenTraverser($this->tokens);
        $this->availableVariables = array_reduce($method->params, function ($carry, $methodParam) {
            $carry[$methodParam->name] = $methodParam->type;

            return $carry;
        }, []);
        $this->availableVariables = array_merge($this->availableVariables, array_reduce($this->class->availableVariables(true), function ($carry, $methodParam) {
            $carry[$methodParam->name] = $methodParam->type;

            return $carry;
        }, []));
        for ($i = 0; $i < count($this->tokens); $i++) {
            $logicalLineEnding = $this->tokenTraverse->traverseTillNextDeclaration($i);
            $this->fixLogicalLine($i, $logicalLineEnding);
            $i = $logicalLineEnding;

        }
        $this->class->fileContents = str_replace(str_replace("\r\n", "\n", implode(null, $originalTokens)), implode(null, $this->tokens), $this->class->fileContents);
        $this->class->fileContents = str_replace("\r", '', $this->class->fileContents);
        file_put_contents($this->class->fileLocation, $this->class->fileContents);
    }

    public function fix(): void
    {
        $needReplacing[] = array_intersect_key(PackageUpdates::methodChanges(), array_flip($this->class->uses));

        $aliases = array_filter($this->class->uses, fn($use) => in_array($use, array_keys($needReplacing[0])));
        $this->tokens = $this->removePhpTags($this->chopTokens(token_get_all($this->class->fileContents)));
        $originalTokens = $this->tokens;
        $this->tokenTraverse = new TokenTraverser($this->tokens);
        for ($i = 0; $i < count($this->tokens); $i++) {
            $logicalLineEnding = $this->tokenTraverse->traverseTillNextDeclaration($i);
            $this->fixLogicalLine($i, $logicalLineEnding);
            $i = $logicalLineEnding;
        }
        $this->class->fileContents = str_replace(str_replace("\r\n", "\n", implode(null, $originalTokens)), implode(null, $this->tokens), $this->class->fileContents);
        $this->class->fileContents = str_replace("\r", '', $this->class->fileContents);
        file_put_contents($this->class->fileLocation, $this->class->fileContents);
    }

    public function fixLogicalLine(int $start, int $end): string
    {
        if (str_contains((string) $this->tokens[$start], ' ')) {
            $start++;
            if ($start === $end) {
                return '';
            }
        }
        $isDeclaring = false;
        if (isset($this->tokens[$start], $this->tokens[$start + 1], $this->tokens[$start + 2])) {
            $isDeclaring = ($this->tokens[$start][0] === '$' && $this->tokens[$start + 1] === ' ' && $this->tokens[$start + 2] === '=');
        }

        $declaringVariable = str_replace('$', '', (string) $this->tokens[$start]);
        $start = $isDeclaring ? $start + 3 : $start;
        $currentType = 'self';
        $declaresObject = $this->tokens[$start] === '(';
        $start = $declaresObject ? $start + 1 : $start;
        for ($i = $start; $i < $end; $i++) {
            if ($this->tokens[$i] === '$this') {
                $currentType = 'self';

            } elseif (in_array($this->tokens[$i], ['->', '::'])) {
                $i++;
                $replacements = PackageUpdates::methodChanges()[$currentType] ?? null;

                if (isset($replacements) && isset($replacements['methods'][$this->tokens[$i]])) {
                    $this->tokens[$i] = $replacements['methods'][$this->tokens[$i]];

                }
                if ($this->tokenTraverse->isVariable($i)) {
                    $currentType = $this->class->variableType($this->tokens[$i]);
                    if (! in_array($currentType, ['void', 'string', 'int', 'array', 'null', ''])) {
                        $currentType = $this->class->uses[$currentType];
                    }

                } else {
                    if ($currentType === 'self') {
                        $currentType = $this->class->methodReturnType($this->tokens[$i]);

                    } elseif (! in_array($currentType, ['void', 'string', 'int', 'array', 'null', ''])) {
                        if (! str_contains((string) $currentType, '\\')) {
                            $currentType = $this->class->uses[$currentType];
                        }
                        $currentType = (new TypeDetector())->methodReturnType($currentType, $this->tokens[$i]);

                    }
                }

            } elseif ($this->tokens[$i] === 'new') {
                $i += 2;
                $currentType = $this->class->uses[$this->tokens[$i]];

            } elseif (in_array($this->tokens[$i], [',', '.'])) {
                $i++;
                $this->fixLogicalLine($i, $end);
                $i = $end;

            } elseif (in_array($this->tokens[$i], ['(', '[', '{'])) {
                $closingTagIndex = $this->tokenTraverse->traverseTillNextParenthesis($this->tokens[$i], $i);
                $enclosedType = $this->fixLogicalLine($i + 1, $closingTagIndex);
                $currentType = $declaresObject && $this->tokens[$i] === '(' ? $enclosedType : $currentType;
                $i = $closingTagIndex + 1;

            } elseif ($this->tokens[$i][0] === '$') {
                $variableDeclaration = str_replace('$', '', (string) $this->tokens[$i]);
                $currentType = $this->availableVariables[$variableDeclaration];

            } elseif (in_array($this->tokens[$i], array_keys($this->class->uses))) {
                $currentType = $this->class->uses[$this->tokens[$i]];

            } elseif (in_array($this->tokens[$i], array_keys(CommonUpdates::commonChanges()['helpers']))) {
                foreach ($this->tokenTraverse->getParams($i) as $param) {

                    if (isset(CommonUpdates::commonChanges()['helpers'][$this->tokens[$i]]['params'][implode('', $param)])) {
                        // TODO: use tokens for replacement or just *file-up-to-this-point*->'replace function call and params with regex'
                        // $change = CommonUpdates::commonChanges()['helpers'][$this->tokens[$i]]['params'][implode('', $param)];
                        /* $tokenized = $this->removePhpTags($this->chopTokens(token_get_all('<?php ' . $change . '?>')));*/
                        // array_splice($this->tokens, $)
                    }
                }
            }
        }
        if ($isDeclaring) {
            $this->availableVariables[$declaringVariable] = $currentType;
        }

        return $currentType ?? 'self';
    }

    private function methodBody(ClassMethod $method): string
    {
        $source = preg_split('/\n/', $this->class->fileContents);
        $body = [];
        for ($i = $method->startLine - 1; $i < $method->endLine; $i++) {
            $body[] = "{$source[$i]}";
        }

        return implode(PHP_EOL, $body);
    }

    private function removePhpTags(array $tokens): array
    {
        array_pop($tokens);
        unset($tokens[0]);

        return array_values($tokens);
    }

    private function chopTokens(array $tokens): array
    {
        $tokenArray = [];
        foreach ($tokens as $token) {
            if (is_array($token)) {
                $tokenArray[] = $token[1];
            } else {
                $tokenArray[] = $token;
            }
        }

        return $tokenArray;
    }
}
