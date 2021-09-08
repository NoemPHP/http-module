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
        private Container        $container,
        string                   ...$routeIds
    )
    {
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
                foreach ($this->getMethods($att->method) as $method) {

                    $r->addRoute(
                        $method,
                        $this->getPath($att),
                        function (
                            ServerRequestInterface $request,
                            RequestHandler         $requestHandler
                        ) use ($handler) {
                            return $this->invoker->call(
                                $handler,
                                [
                                    ServerRequestInterface::class => $request,
                                ]
                            );
                        });
                }
            }
        }
    }

    private function getMethods(int $methodFlags): iterable
    {
        if (($methodFlags & Route::GET) == Route::GET) {
            yield 'GET';
        }
        if (($methodFlags & Route::POST) == Route::POST) {
            yield 'POST';
        }
        if (($methodFlags & Route::PUT) == Route::PUT) {
            yield 'PUT';
        }
        if (($methodFlags & Route::DELETE) == Route::DELETE) {
            yield 'DELETE';
        }
        if (($methodFlags & Route::PATCH) == Route::PATCH) {
            yield 'PATCH';
        }
    }

    private function getPath(Route $route)
    {
        $path = $route->path;
        if (str_starts_with('@', $path)) {
            return $this->container->get(substr($path, 1));
        }

        return $path;
    }
}
