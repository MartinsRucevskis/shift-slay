<?php

declare(strict_types=1);

namespace App\Shift\TokenTraverser;

class TokenTraverser
{
    /** @var string[] */
    private array $methodAccessors = ['->', '::'];

    /**
     * @param  string[]  $tokens
     */
    public function __construct(private readonly array $tokens)
    {
    }

    public function traverseTillNextDeclaration(int $start, string $separator = ';'): int
    {
        // TODO: Dont process comments, exception if wrong brace/parenthesis count, loops
        $braceCount = 0;
        $parenthesisCount = 0;
        $counter = count($this->tokens);
        for (; $start < $counter; $start++) {
            if ($this->tokens[$start] === '{') {
                $braceCount++;
            } elseif ($this->tokens[$start] === '}') {
                $braceCount--;
            } elseif ($this->tokens[$start] === '(') {
                $parenthesisCount++;
            } elseif ($this->tokens[$start] === ')') {
                $parenthesisCount--;
            }

            if ($braceCount === 0 && $parenthesisCount === 0 && $this->tokens[$start] === $separator) {
                return $start;
            }
        }

        return $start;
    }

    public function isObjectDeclreation(int $start): bool
    {
        return ($this->tokens[$start] === '(' && $this->tokens[$start + 1] === 'new')
            || $this->tokens[$start] === 'new';
    }

    public function ignoreWhiteSpaces(int $start, int $end): int
    {
        while (str_contains($this->tokens[$start], ' ') && $start <= $end) {
            $start++;
        }

        return $start;
    }

    public function isDeclaring(int $start): bool
    {
        if (isset($this->tokens[$start], $this->tokens[$start + 1], $this->tokens[$start + 2])) {
            return $this->isVariableToken($start) && $this->tokens[$start + 1] === ' ' && $this->tokens[$start + 2] === '=';
        }

        return false;
    }

    public function declaringVariable(int $start): string
    {
        return str_replace('$', '', $this->tokens[$start]);
    }

    public function isVariableToken(int $start): bool
    {
        return $this->tokens[$start][0] === '$';
    }

    public function isDeclaringObject(int $start): bool
    {
        return $this->tokens[$start] === '(';
    }

    public function updateStart(bool $isDeclaring, bool $declaresObject, int $start): int
    {
        $start = $isDeclaring ? $start + 3 : $start;

        return $declaresObject ? $start + 1 : $start;
    }

    public function isReferringToSelf(int $start): bool
    {
        return $this->tokens[$start] === '$this';
    }

    public function isMethodAccessor(int $start): bool
    {
        return in_array($this->tokens[$start], $this->methodAccessors);
    }

    public function isDeclaringNewObject(int $start): bool
    {
        return $this->tokens[$start] === 'new';
    }

    public function isVariable(int $start): bool
    {
        if ($this->isVariableToken($start)) {
            return true;
        }

        return $this->tokens[$start + 1] !== '(';
    }

    public function traverseTillNextParenthesis(string $parenthesisType, int $position): int
    {
        $parenthesisMap = ['(' => ')', '{' => '}', '[' => ']'];
        if (! array_key_exists($parenthesisType, $parenthesisMap)) {
            throw new \Exception("This ain't a parenthesis you foo");
        }

        $parenthesisCount = 0;
        $closingParenthesis = $parenthesisMap[$parenthesisType];
        do {
            if ($this->tokens[$position] === $parenthesisType) {
                $parenthesisCount++;
            } elseif ($this->tokens[$position] === $closingParenthesis) {
                $parenthesisCount--;
            }

            $position++;
        } while ($parenthesisCount !== 0);

        return $position - 1;
    }

    public function isNewLine(string $token): bool
    {
        return ord($token) === 13;
    }

    /**
     * @return  array<int, array<string>>.
     */
    public function getParams(int $start): array
    {
        $start++;
        $ending = $this->traverseTillNextParenthesis($this->tokens[$start], $start);
        $params = [];
        for ($i = $start; $i < $ending; $i++) {
            $endingForParam = $this->traverseTillNextDeclaration($start, ',');
            if ($ending > $endingForParam) {
                $params[] = array_slice($this->tokens, $i, $endingForParam - 1);
                $i = $endingForParam - 1;
                $start = $endingForParam - 1;
            } else {
                $i = $ending;
            }
        }

        $params[] = array_slice($this->tokens, $start + 1, ($ending - $start - 1));

        return $params;
    }
}
