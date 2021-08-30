<?php

declare(strict_types=1);

namespace Noem\Http;

use FastRoute\RouteCollector;
use Invoker\InvokerInterface;
use Noem\Http\Attribute\Route;
use Psr\Http\Message\ServerRequestInterface;
use ReflectionFunction;
use Relay\RequestHandler;

class RouteLoader
{
    public function __construct(private InvokerInterface $invoker)
    {
    }

    public function __invoke(RouteCollector $r, callable ...$routes)
    {
        foreach ($routes as $handler) {
            $refFunc = new ReflectionFunction($handler);
            $atts = $refFunc->getAttributes(Route::class);
            if (empty($atts)) {
                continue;
            }
            $att = null;
            foreach ($atts as $att) {
                $att = $att->newInstance();
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
