<?php

namespace App\Shift\TokenTraverser;

class TokenTraverser
{
    /**
     * @param string[] $tokens
     */
    public function __construct(private readonly array $tokens)
    {
    }

    public function traverseTillNextDeclaration(int $start, string $separator = ';'): int
    {
        // TODO: Dont process comments, exception if wrong brace/parenthesis count, loops
        $braceCount = 0;
        $parenthesisCount = 0;
        for (; $start < count($this->tokens); $start++) {
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

    public function isVariable(int $start): bool
    {
        return $this->tokens[$start][0] === '$' || $this->tokens[$start + 1] !== '(';
    }

    public function traverseTillNextParenthesis(string $parenthesisType, int $position): int
    {
        $parenthesisMap = ['(' => ')', '{' => '}', '[' => ']'];
        if (! array_key_exists($parenthesisType, $parenthesisMap)) {
            throw new \Exception('This ain\'t a parenthesis you foo');
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
                $i = $start = $endingForParam - 1;
            } else {
                $i = $ending;
            }
        }
        $params[] = array_slice($this->tokens, $start + 1, ($ending - $start - 1));

        return $params;
    }
}
