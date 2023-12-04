<?php

namespace Tests\Support\HttpMock;

use Illuminate\Http\Client\Request;

class MockResponseConditions
{
    private ?string $bodyContains = null;

    private string $requestMethod = '';

    public function __construct(private readonly HttpMockResponse $response)
    {
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

    public function then(): HttpMockResponse
    {
        return $this->response;
    }

    public function conditionsMatchRequest(Request $request): bool
    {
        return ($this->requestMethod === '' || $request->method() === $this->requestMethod)
            && ($this->bodyContains === null || str_contains($request->body(), $this->bodyContains));
    }
}
