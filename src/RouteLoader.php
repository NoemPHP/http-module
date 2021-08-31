<?php

declare(strict_types=1);

namespace Noem\Http;

use FastRoute\RouteCollector;
use Invoker\InvokerInterface;
use Noem\Container\AttributeAwareContainer;
use Noem\Container\Container;
use Noem\Http\Attribute\Route;
use Psr\Http\Message\ServerRequestInterface;
use ReflectionFunction;
use Relay\RequestHandler;

class RouteLoader
{
    /**
     * @var string[]
     */
    private array $routeIds;

    public function __construct(
        private InvokerInterface $invoker,
        private Container $container,
        string ...$routeIds
    ) {
        $this->routeIds = $routeIds;
    }

    public function __invoke(RouteCollector $r)
    {
        foreach ($this->routeIds as $id) {
            $attributesOfId = $this->container->getAttributesOfId($id, Route::class);
            if (empty($attributesOfId)) {
                continue;
            }
            $handler = $this->container->get($id);
            foreach ($attributesOfId as $att) {
                assert($att instanceof Route);
                switch ($att->method) {
                    case 'GET':
                        $r->get(
                            $att->path,
                            function (
                                ServerRequestInterface $request,
                                RequestHandler $requestHandler
                            ) use ($handler) {
                                $this->invoker->call(
                                    $handler,
                                    [
                                        ServerRequestInterface::class => $request
                                    ]
                                );
                            }
                        );
                }
            }
        }
    }
}
