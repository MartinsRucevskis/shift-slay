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
    /**
     * @var string[]
     */
    private array $tokens = [];

    private TokenTraverser $tokenTraverse;

    /**
     * @var array<string, string|null>
     */
    private array $availableVariables = [];

    public function __construct(private readonly FileClass $class)
    {

    }

    public function fixMethod(ClassMethod $method): void
    {
        $needReplacing[] = array_intersect_key(PackageUpdates::methodChanges(), array_flip($this->class->uses));
        preg_match('/.*?function.*?\(.*?\):? ?(.*?)[\s+]?{(.*)}/ms', $this->methodBody($method), $matches);
        $this->tokens = $this->removePhpTags($this->chopTokens(token_get_all('<?php'.$matches[2].'?>')));
        $originalTokens = $this->tokens;
        $this->tokenTraverse = new TokenTraverser($this->tokens);
        $this->availableVariables = array_reduce($method->params, static function (array $carry, $methodParam): array {
            $carry[$methodParam->name] = $methodParam->type;

            return $carry;
        }, []);
        $this->availableVariables = array_merge($this->availableVariables, array_reduce($this->class->availableVariables(true), static function (array $carry, $methodParam): array {
            $carry[$methodParam->name] = $methodParam->type;

            return $carry;
        }, []));
        $counter = count($this->tokens);
        for ($i = 0; $i < $counter; $i++) {
            $logicalLineEnding = $this->tokenTraverse->traverseTillNextDeclaration($i);
            $this->fixLogicalLine($i, $logicalLineEnding);
            $i = $logicalLineEnding;

        }

        $this->class->fileContents = str_replace(str_replace("\r\n", "\n", implode('', $originalTokens)), implode('', $this->tokens), $this->class->fileContents);
        $this->class->fileContents = str_replace("\r", '', $this->class->fileContents);
        file_put_contents($this->class->fileLocation, $this->class->fileContents);
    }

    public function fix(): void
    {
        $this->tokens = $this->removePhpTags($this->chopTokens(token_get_all($this->class->fileContents)));
        $originalTokens = $this->tokens;
        $this->tokenTraverse = new TokenTraverser($this->tokens);
        $counter = count($this->tokens);
        for ($i = 0; $i < $counter; $i++) {
            $logicalLineEnding = $this->tokenTraverse->traverseTillNextDeclaration($i);
            $this->fixLogicalLine($i, $logicalLineEnding);
            $i = $logicalLineEnding;
        }

        $this->class->fileContents = str_replace(str_replace("\r\n", "\n", implode('', $originalTokens)), implode('', $this->tokens), $this->class->fileContents);
        $this->class->fileContents = str_replace("\r", '', $this->class->fileContents);
        file_put_contents($this->class->fileLocation, $this->class->fileContents);
    }

    public function fixLogicalLine(int $start, int $end): string
    {
        $start = $this->tokenTraverse->ignoreWhitespaces($start, $end);
        $isDeclaring = $this->tokenTraverse->isDeclaring($start);
        $currentType = 'self';
        $declaringVariable = $this->tokenTraverse->declaringVariable($start);
        $declaresObject = $this->tokenTraverse->isDeclaringObject($start);

        $start = $this->tokenTraverse->updateStart($isDeclaring, $declaresObject, $start);

        for ($i = $start; $i < $end; $i++) {
            if ($this->tokenTraverse->isReferringToSelf($i)) {
                $currentType = 'self';
            } elseif ($this->tokenTraverse->isMethodAccessor($i)) {
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
                } elseif ($currentType === 'self') {
                    $currentType = $this->class->methodReturnType($this->tokens[$i]);
                } elseif (! in_array($currentType, ['void', 'string', 'int', 'array', 'null', ''])) {
                    if (! str_contains((string) $currentType, '\\')) {
                        $currentType = $this->class->uses[$currentType];
                    }

                    $currentType = (new TypeDetector())->methodReturnType($currentType, $this->tokens[$i]);

                }

            } elseif ($this->tokenTraverse->isDeclaringNewObject($i)) {
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

            } elseif ($this->tokenTraverse->isVariableToken($start)) {
                $variableDeclaration = $this->tokenTraverse->declaringVariable($i);
                $currentType = $this->availableVariables[$variableDeclaration];

            } elseif (in_array($this->tokens[$i], array_keys($this->class->uses))) {
                $currentType = $this->class->uses[$this->tokens[$i]];

            } elseif (in_array($this->tokens[$i], array_keys(CommonUpdates::commonChanges()['helpers']))) {
                foreach ($this->tokenTraverse->getParams($i) as $param) {

                    if (isset(CommonUpdates::commonChanges()['helpers'][$this->tokens[$i]]['params'][implode('', $param)])) {
                        // TODO: use tokens for replacement or just *file-up-to-this-point*->'replace function call and params with regex'
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
        $source = preg_split('/\n/', $this->class->fileContents) ?: [];
        $body = [];
        for ($i = $method->startLine - 1; $i < $method->endLine; $i++) {
            $body[] = $source[$i];
        }

        return implode(PHP_EOL, $body);
    }

    /**
     * @param  string[]  $tokens
     * @return string[]
     */
    private function removePhpTags(array $tokens): array
    {
        array_pop($tokens);
        unset($tokens[0]);

        return array_values($tokens);
    }

    /**
     * @param  mixed[]  $tokens
     * @return string[]
     */
    private function chopTokens(array $tokens): array
    {
        $tokenArray = [];
        foreach ($tokens as $token) {
            $tokenArray[] = is_array($token) ? $token[1] : $token;
        }

        return $tokenArray;
    }
}
