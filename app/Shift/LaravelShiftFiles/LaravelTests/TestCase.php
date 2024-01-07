<?php

namespace Tests;

use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Http\Client\Request;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Tests\Support\HttpMock\HttpStatelessMock;

use function Safe\file_get_contents;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;
    use HttpStatelessMock;
    use RefreshDatabase;

    protected string $seeder = TestSeeder::class;

    protected function tearDown(): void
    {
        $this->resetMock();
        parent::tearDown();
    }

    public function jsonFileContentAsArray(string $fileName): array
    {
        return json_decode(file_get_contents($fileName), true);
    }

    public function mockResponse(
        string $directory,
        string $mockFilename,
        ?string $type = null
    ): string {
        return $this->mockFileContent(
            $directory,
            $mockFilename,
            'Response',
            $type
        );
    }

    public function mockRequest(
        string $directory,
        string $mockFilename,
        ?string $type = null
    ): array {
        return json_decode($this->mockFileContent(
            $directory,
            $mockFilename,
            'Request',
            $type
        ), true);
    }

    public function outgoingRequest(string $url, ?string $method = null, ?string $body = null, bool $bodyMatchExact = false): Request
    {
        return $this->outgoingRequests($url)->first();
    }

    public function outgoingRequests(string $url, ?string $method = null, ?string $body = null, bool $bodyMatchExact = false): Collection
    {
        /** @var Collection $traffic */
        return Http::recorded(function (Request $request, Response $response) use ($url, $method, $body, $bodyMatchExact) {
            return str_contains($request->toPsrRequest()->getUri()->getPath(), $url)
                && (! isset($method) || $request->toPsrRequest()->getMethod() === $method)
                && (! isset($body) || (
                    ($bodyMatchExact && $request->toPsrRequest()->getBody()->getContents() === $body)
                    || (! $bodyMatchExact && str_contains($request->toPsrRequest()->getBody()->getContents(), $body))
                )
                );
        })->map(function ($item) {
            return $item[0];
        })->values();
    }

    protected function mockActiveTokenResponse(): void
    {
        Http::fake(
            [config('services.oauth.url').'/token_info' => Http::response(
                ['active' => true],
                200,
                ['Content-Type', 'application/json; charset=utf-8']
            )]
        );
    }

    private function mockFileContent(
        string $directory,
        string $mockFilename,
        ?string $type = null,
        ?string $subType = null
    ): string {
        return file_get_contents((new Collection(
            [$directory, 'Mock', $type, $subType, $mockFilename]
        ))->filter()->map(function ($value, $key) {
            return ($key === 0 ? 'rtrim' : 'trim')($value, '/');
        })->join('/'));
    }
}
