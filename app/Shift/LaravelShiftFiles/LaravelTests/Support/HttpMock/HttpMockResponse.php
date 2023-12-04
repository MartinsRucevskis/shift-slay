<?php

namespace Tests\Support\HttpMock;

use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

class HttpMockResponse
{
    private null|array|string $body = null;

    private int $statusCode = 200;

    private ?MockResponseConditions $responseConditions = null;

    private array $headers = [];

    public function when(): MockResponseConditions
    {
        if ($this->responseConditions === null) {
            $this->responseConditions = new MockResponseConditions($this);
        }

        return $this->responseConditions;
    }

    public function respondWithBody(null|array|string $body = ''): self
    {
        $this->body = $body;

        return $this;
    }

    public function respondWithStatusCode(int $statusCode): self
    {
        $this->statusCode = $statusCode;

        return $this;
    }

    public function withHeaders(array $headers): self
    {
        $this->headers = $headers;

        return $this;
    }

    public function matchesRequest(Request $request): bool
    {
        return $this->responseConditions === null || $this->responseConditions->conditionsMatchRequest($request);
    }

    public function asHttpResponse(): PromiseInterface
    {
        return Http::response($this->body, $this->statusCode, $this->headers);
    }
}
