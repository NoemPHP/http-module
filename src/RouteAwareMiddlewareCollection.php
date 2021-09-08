<?php

namespace Noem\Http;

use FastRoute\Dispatcher;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;

class RouteAwareMiddlewareCollection
{
    /**
     * @var MiddlewareDefinition[]
     */
    private array $middlewareDefinitions;

    public function __construct(MiddlewareDefinition ...$definitions)
    {
        usort(
            $definitions,
            fn(MiddlewareDefinition $a, MiddlewareDefinition $b) => $a->priority <=> $b->priority
        );
        $this->middlewareDefinitions = $definitions;
    }

    public function forRequest(ServerRequestInterface $request): array
    {
        $uri = rawurldecode($request->getUri()->getPath());
        $result = [];
        foreach ($this->middlewareDefinitions as $definition) {
            if (!in_array($request->getMethod(), $definition->methods)) {
                continue;
            }
            if (!preg_match('~' . $definition->path . '~', $uri)) {
                continue;
            }
            $result[] = $definition->middleware;
        }
        return $result;
    }
}
