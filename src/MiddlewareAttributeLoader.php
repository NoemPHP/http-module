<?php

declare(strict_types=1);

namespace Noem\Http;

use FastRoute\RouteCollector;
use Noem\Container\Container;
use Noem\Http\Attribute\Middleware;

class MiddlewareAttributeLoader
{
    public function __construct(private Container $container)
    {

    }

    /**
     * @return MiddlewareDefinition[]
     * @throws \Noem\Container\Exception\NotFoundException
     * @throws \Noem\Container\Exception\ServiceInvokationException
     */
    public function load(): array
    {
        $result = [];
        foreach ($this->getMiddlewareServices() as [$id, $middlewareAttrs]) {
            /**
             * @var Middleware[] $middlewareAttrs
             */
            foreach ($middlewareAttrs as $att) {
                $result[] = new MiddlewareDefinition(
                    $this->container->get($id),
                    $att->path,
                    $att->method,
                    $att->priority
                );

            }
        };
        return $result;
    }

    private function getMiddlewareServices(): array
    {
        return array_map(
            fn(string $id) => [$id, $this->container->getAttributesOfId($id, Middleware::class)],
            $this->container->getIdsWithAttribute(Middleware::class)
        );
    }
}
