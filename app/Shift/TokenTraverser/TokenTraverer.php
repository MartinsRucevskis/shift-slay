<?php

namespace App\Shift\TokenTraverser;

class TokenTraverer
{
    public function __construct(private readonly array $tokens)
    {
    }

    public function traverseTillNextDeclaration(int $start, string $separator = ';'): int{
        // TODO: Dont process comments, exception if wrong brace/parenthesis count, loops
        $braceCount = 0;
        $parenthesisCount = 0;
        for(;$start < count($this->tokens); $start++){
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

    public function isObjectDeclreation(int $start): bool{
        return ($this->tokens[$start] === '(' && $this->tokens[$start+1] === 'new')
            || $this->tokens[$start] === 'new';
    }

    public function isVariable(int $start): bool{
        return $this->tokens[$start][0] === '$' || $this->tokens[$start+1] !== '(';
    }

    public function traverseTillNextParenthesis(string $parenthesisType, int $start): int{
        if($parenthesisType !== '(' && $parenthesisType !== '{' && $parenthesisType !== '['){
            throw new \Exception('This ain\'t a parenthesis you foo');
        }
        $parenthesisCount = 1;
        $ending = $start;
        $start++;
        $parenthesisMap = ['(' => ')', '{' => '}', '[' => ']'];
        for ($i = $start; $parenthesisCount !== 0 ; $i++){
            $ending++;
            if ($parenthesisType === $this->tokens[$i]){
                $parenthesisCount++;
            } elseif ( $this->tokens[$i] === $parenthesisMap[$parenthesisType]){
                $parenthesisCount--;
            }
        }
        return $ending;
    }

    public function ignoreComment(int $start): int{
    }

    public function isNewLine(string $token): bool{
        return ord($token) === 13;
    }

    public function getParams(int $start){
        $start++;
        $ending = $this->traverseTillNextParenthesis($this->tokens[$start], $start);
        $params = [];
        for ($i = $start; $i < $ending; $i++){
            $endingForParam = $this->traverseTillNextDeclaration($start, ',');
            if($ending > $endingForParam){
                $params[] = array_slice($this->tokens, $i, $endingForParam-1);
                $i = $start = $endingForParam-1;
            } else {
               $i = $ending;
            }
        }
        $params[] = array_slice($this->tokens, $start+1, ($ending-$start-1));

        return $params;
    }
}
