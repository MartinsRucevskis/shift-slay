<?php

namespace Tests\Support\HttpMock;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

class HttpMock
{
    /**
     * @var HttpMockResponse[]
     */
    private array $responses;

    public function __construct(private readonly string $url)
    {
    }

    public function url(): string
    {
        return $this->url;
    }

    public function addResponse(HttpMockResponse $response): self
    {
        $this->responses[] = $response;

        return $this;
    }

    public function buildMock(): void
    {
        Http::fake([
            $this->url => function (Request $request) {
                foreach ($this->responses as $response) {
                    if ($response->matchesRequest($request)) {
                        return $response->asHttpResponse();
                    }
                }
            },
        ]);
    }
}
