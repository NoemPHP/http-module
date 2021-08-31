<?php

declare(strict_types=1);


use Noem\Http\Attribute\Route;
use Noem\Http\HttpRequestEvent;
use Noem\Http\ResponseEmitter;
use Noem\IntegrationTest\NoemFrameworkTestCase;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7Server\ServerRequestCreator;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class HttpRequestTest extends NoemFrameworkTestCase
{

    public function setUp(): void
    {
        $this->response = null;
        parent::setUp();
    }

    public function testEmitsResponse()
    {
        $response = $this->dispatchRequest($this->createRequest());
        $this->assertNotNull($response);
        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertSame(200, $response->getStatusCode());
    }

    protected function createRequest(string $path = '/'): ServerRequestInterface
    {
        $creator = new ServerRequestCreator(...array_fill(0, 4, new Psr17Factory()));
        return $creator->fromArrays([
                                        'REQUEST_METHOD' => 'GET',
                                        'REQUEST_URI' => $path
                                    ]);
    }

    public function testEmits404ForUnknownRoute()
    {
        $response = $this->dispatchRequest($this->createRequest('/nowhere'));
        $this->assertNotNull($response);
        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertSame(404, $response->getStatusCode());
    }

    public function dispatchRequest(ServerRequestInterface $request): ResponseInterface
    {
        $dispatcher = $this->getContainer()->get(EventDispatcherInterface::class);
        $requestEvent = new HttpRequestEvent($request);
        $dispatcher->dispatch($requestEvent);
        return $requestEvent->response();
    }

    protected function getFactories(): array
    {
        return [
            'someroute' =>
                #[Route('/')]
                fn() => function () {
                }
        ];
    }

    protected function getExtensions(): array
    {
        return [];
    }
}
