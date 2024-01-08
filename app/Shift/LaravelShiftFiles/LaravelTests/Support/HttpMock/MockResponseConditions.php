<?php

namespace Tests\Support\HttpMock;

use Illuminate\Http\Client\Request;
use function Safe\preg_match;

class MockResponseConditions
{
    private ?string $bodyContains = null;

    private ?string $bodyContainsRegex = null;

    private string $requestMethod = '';

    public function __construct(private readonly HttpMockResponse $response)
    {
    }

    public function method(string $method): self
    {
        $this->requestMethod = $method;

        return $this;
    }

    public function get(): self
    {
        $this->requestMethod = 'GET';

        return $this;
    }

    public function patch(): self
    {
        $this->requestMethod = 'PATCH';

        return $this;
    }

    public function post(): self
    {
        $this->requestMethod = 'POST';

        return $this;
    }

    public function delete(): self
    {
        $this->requestMethod = 'DELETE';

        return $this;
    }

    public function bodyIsContaining(string $requestBody): self
    {
        $this->bodyContains = $requestBody;

        return $this;
    }

    public function bodyIsContainingRegex(string $regex): self
    {
        $this->bodyContainsRegex = $regex;

        return $this;
    }

    public function then(): HttpMockResponse
    {
        return $this->response;
    }

    public function conditionsMatchRequest(Request $request): bool
    {
        return $this->matchesRequestMethod($request->method()) && $this->matchBody($request->body());
    }

    private function matchBody(string $body): bool
    {
        $matches = true;
        if (isset($this->bodyContains)) {
            $matches = str_contains($body, $this->bodyContains);
        } elseif (isset($this->bodyContainsRegex)) {
            $matches = preg_match($this->bodyContainsRegex, $body) === 1;
        }

        return $matches;
    }

    private function matchesRequestMethod(string $method): bool
    {
        return $this->requestMethod === '' || $method === $this->requestMethod;
    }
}
