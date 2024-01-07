<?php

namespace Tests\Support\HttpMock;

use Illuminate\Support\Facades\Http;
use Illuminate\Testing\TestResponse;
use ReflectionObject;

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
        $reflection = new ReflectionObject(Http::getFacadeRoot());
        $property = $reflection->getProperty('stubCallbacks');
        $property->setAccessible(true);
        $property->setValue(Http::getFacadeRoot(), collect());
        $property->setAccessible(false);
        $this->mocks = [];
        $this->hasBeenInitiated = false;
    }

    public function buildMock(): void
    {
        if (! $this->hasBeenInitiated) {
            $this->initiateMock();
        }
    }

    /**
     * @param  string  $uri
     * @param  int  $options
     */
    public function getJson($uri, array $headers = [], $options = 0): TestResponse
    {
        $this->buildMock();

        return parent::getJson($uri, $headers, $options);
    }

    /**
     * @param  string  $uri
     * @param  int  $options
     */
    public function postJson($uri, array $data = [], array $headers = [], $options = 0): TestResponse
    {
        $this->buildMock();

        return parent::postJson($uri, $data, $headers, $options);
    }

    /**
     * @param  string  $uri
     * @param  int  $options
     */
    public function patchJson($uri, array $data = [], array $headers = [], $options = 0): TestResponse
    {
        $this->buildMock();

        return parent::patchJson($uri, $data, $headers, $options);
    }

    /**
     * @param  string  $uri
     * @param  int  $options
     */
    public function deleteJson($uri, array $data = [], array $headers = [], $options = 0): TestResponse
    {
        $this->buildMock();

        return parent::deleteJson($uri, $data, $headers, $options);
    }

    public static function httpMockResponse(): HttpMockResponse
    {
        return new HttpMockResponse();
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
}
