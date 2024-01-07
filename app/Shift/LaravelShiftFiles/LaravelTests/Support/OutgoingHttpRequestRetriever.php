<?php

namespace Tests\Support;

use Illuminate\Http\Client\Request;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

trait OutgoingHttpRequestRetriever
{
    public function outgoingRequest(string $url = '', ?string $method = null, ?string $body = null, bool $matchBodyExactly = false): Request
    {
        return $this->outgoingRequests($url, $method, $body, $matchBodyExactly)[0];
    }

    public function outgoingRequestsRegex(string $regex, string $url = '', ?string $method = null): array
    {
        $outgoingRequests = array_filter($this->outgoingRequests($url, $method), function ($request) use ($regex) {
            return preg_match($regex.'ms', $request->body()) === 1;
        });

        return array_values($outgoingRequests);
    }

    public function outgoingRequests(string $url = '', ?string $method = null, ?string $body = null, bool $matchBodyExactly = false): array
    {
        return array_values(
            Http::recorded(function (Request $request, Response $response) use ($url, $method, $body, $matchBodyExactly) {
                return $this->isRequestMatchingConditions($request, $url, $method, $body, $matchBodyExactly);
            })->map(function ($item) {
                return $item[0];
            })->toArray()
        );
    }

    private function isRequestMatchingConditions(Request $request, string $url, ?string $method, ?string $body, bool $matchBodyExactly): bool
    {
        return $this->isUrlMatching($request, $url)
            && $this->isMethodMatching($request, $method)
            && $this->isBodyMatching($request, $body, $matchBodyExactly);
    }

    private function isUrlMatching(Request $request, string $url): bool
    {
        return str_contains($request->toPsrRequest()->getUri()->getPath(), $url);
    }

    private function isMethodMatching(Request $request, ?string $method): bool
    {
        return ! isset($method) || $request->toPsrRequest()->getMethod() === $method;
    }

    private function isBodyMatching(Request $request, ?string $body, bool $bodyMatchExact): bool
    {
        if (! isset($body)) {
            return true;
        }

        return $bodyMatchExact
            ? $this->isExactBodyMatch($request, $body)
            : $this->isPartialBodyMatch($request, $body);
    }

    private function isExactBodyMatch(Request $request, string $body): bool
    {
        return $request->body() === $body;
    }

    private function isPartialBodyMatch(Request $request, string $body): bool
    {
        return str_contains($request->body(), $body);
    }
}
