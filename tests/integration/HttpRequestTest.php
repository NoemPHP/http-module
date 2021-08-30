<?php

declare(strict_types=1);


use Noem\Http\ResponseEmitter;
use Noem\IntegrationTest\NoemFrameworkTestCase;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7Server\ServerRequestCreator;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseInterface;

class HttpRequestTest extends NoemFrameworkTestCase
{
    private ?ResponseInterface $response = null;

    public function setUp(): void
    {
        $this->response = null;
        parent::setUp();
    }

    public function testEmitsResponse()
    {
        $e = $this->getContainer()->get(EventDispatcherInterface::class);
        $creator = new ServerRequestCreator(...array_fill(0, 4, new Psr17Factory()));
        $request = $creator->fromArrays([
                                            'REQUEST_METHOD' => 'GET'
                                        ]);
        $e->dispatch($request);
        $this->assertNotNull($this->response);
        $this->assertInstanceOf(ResponseInterface::class, $this->response);
    }

    protected function getFactories(): array
    {
        return [
            ResponseEmitter::class => fn() => $this->mockEmitter()
        ];
    }

    private function mockEmitter()
    {
        $emitter = Mockery::mock(ResponseEmitter::class);
        $emitter->shouldReceive('emit')->andReturnUsing(function ($r) {
            $this->response = $r;
        });
        return $emitter;
    }

    protected function getExtensions(): array
    {
        return [];
    }
}
