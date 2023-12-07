<?php

namespace Tests\Support\HttpMock;

use Illuminate\Support\Facades\Http;
use Illuminate\Testing\TestResponse;

trait HttpStatelessMock
{
    /**
     * @var HttpMock[]
     */
    private array $mocks = [];

    private bool $hasBeenInitiated = false;

    public function httpMock(string $url = '*'): HttpMock
    {
        foreach ($this->mocks as $mock) {
            if ($mock->url() === $url) {
                return $mock;
            }
        }
        $mock = new HttpMock($url);
        $this->mocks[] = $mock;

        return $mock;
    }

    public function resetMock(): void
    {
        $this->mocks = [];
        $this->hasBeenInitiated = false;
    }

    public function getJson($uri, array $headers = [], $options = 0): TestResponse
    {
        if (! $this->hasBeenInitiated) {
            $this->initiateMock();
        }

        return parent::getJson($uri, $headers, $options);
    }

    public function postJson($uri, array $data = [], array $headers = [], $options = 0): TestResponse
    {
        if (! $this->hasBeenInitiated) {
            $this->initiateMock();
        }

        return parent::postJson($uri, $data, $headers, $options);
    }

    public function patchJson($uri, array $data = [], array $headers = [], $options = 0): TestResponse
    {
        if (! $this->hasBeenInitiated) {
            $this->initiateMock();
        }

        return parent::patchJson($uri, $data, $headers, $options);
    }

    public function deleteJson($uri, array $data = [], array $headers = [], $options = 0): TestResponse
    {
        if (! $this->hasBeenInitiated) {
            $this->initiateMock();
        }

        return parent::deleteJson($uri, $data, $headers, $options);
    }

    private function initiateMock(): void
    {
        if ($this->mocks === []) {
            Http::fake();
        }
        foreach ($this->mocks as $mock) {
            $mock->buildMock();
        }
        $this->hasBeenInitiated = true;
    }

    public static function httpMockResponse(): HttpMockResponse
    {
        return new HttpMockResponse();
    }
}
