<?php

declare(strict_types=1);

use FastRoute\RouteCollector;
use Invoker\InvokerInterface;
use Noem\Container\Attribute\Description;
use Noem\Container\Attribute\Id;
use Noem\Container\Attribute\Tag;
use Noem\Container\Attribute\Tagged;
use Noem\Container\Attribute\WithAttr;
use Noem\Container\AttributeAwareContainer;
use Noem\Container\Container;
use Noem\Container\ContainerHtmlRenderer;
use Noem\Http\Attribute\Middleware;
use Noem\Http\Attribute\Route;
use Noem\Http\HttpRequestEvent;
use Noem\Http\ResponseEmitter;
use Noem\Http\RouteLoader;
use Noem\StateMachineModule\Attribute\Action;
use Noem\StateMachineModule\Attribute\OnEntry;
use Noem\StateMachineModule\Attribute\State;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7Server\ServerRequestCreator;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Relay\Relay;

use function FastRoute\simpleDispatcher;

return [
    'state.http-ready' =>
        #[State(name: 'http-ready', parent: 'on')]
        fn() => state(),
    'on-entry.http-ready' =>
        #[OnEntry(state: 'http-ready')]
        fn() => function () {
        },

    'action.http-ready' =>
        #[Action(state: 'http-ready')]
        fn(
            #[Id('http.request-handler')] RequestHandlerInterface $requestHandler,
            ResponseEmitter $emitter
        ) => function (
            HttpRequestEvent $request
        ) use ($requestHandler, $emitter) {
            $request->setResponse($requestHandler->handle($request->request()));
        },
    'http.request' =>
        function () {
            if (PHP_SAPI === 'cli') {
                throw new Exception('Cannot create global Request object in a CLI call');
            }
            $creator = new ServerRequestCreator(...array_fill(0, 4, new Psr17Factory()));

            return $creator->fromGlobals();
        },
    'http.request.event' =>
        function (#[Id('http.request')] ServerRequestInterface $request) {
            return new HttpRequestEvent($request);
        },
    //    'http.home-route' =>
    //        #[Route('/')]
    //        fn(Container $container) => function (ServerRequestInterface $r) use ($container) {
    //            $containerContents = (new ContainerHtmlRenderer($container->report()))->render();
    //            echo <<<HTML
    //<html>
    //    <head></head>
    //    <body>
    //        <h1>Hello World, Noem here</h1>
    //        <style>
    //        thead,
    //tfoot {
    //    background-color: #3f87a6;
    //    color: #fff;
    //}
    //
    //tbody {
    //    background-color: #e4f0f5;
    //}
    //
    //caption {
    //    padding: 10px;
    //    caption-side: bottom;
    //}
    //
    //table {
    //    border-collapse: collapse;
    //    border: 2px solid rgb(200, 200, 200);
    //    letter-spacing: 1px;
    //    font-family: sans-serif;
    //    font-size: .8rem;
    //}
    //
    //td,
    //th {
    //    border: 1px solid rgb(190, 190, 190);
    //    padding: 5px 10px;
    //}
    //
    //td {
    //    text-align: center;
    //}
    //</style>
    //        {$containerContents}
    //    </body>
    //</html>
    //HTML;
    //        },
    'http.route-loader' => function (InvokerInterface $i, Container $c) {
        $routeIds = $c->getIdsWithAttribute(Route::class);

        return new RouteLoader($i, $c, ...$routeIds);
    },
    'http.fast-route' =>
        #[Tag('http.middleware', 999)]
        fn(#[Id('http.route-loader')] $loader) => new Middlewares\FastRoute(simpleDispatcher($loader)),
    'http.middlewares' =>
        #[Description(
            'The list of PSR-15 Middlewares to use. 
            Fed from the "http.middleware" tag, but may be modified by extensions'
        )]
        fn(#[Tagged('http.middleware')] MiddlewareInterface ...$handlers): array => $handlers,
    'http.request-handler' => function (
        #[Id('http.middlewares')] MiddlewareInterface ...$handlers
    ): RequestHandlerInterface {
        $middlewares = [
            new Middlewares\ContentType(),
            ...$handlers,
            new Middlewares\RequestHandler(),
        ];

        return new Relay($middlewares);
    },
    'request-listener' =>
        #[Tag('event-listener')]
        fn(
            ContainerInterface $c
        ) => function (
            HttpRequestEvent $request,
        ) use ($c) {
            $c->get('state-machine')->action($request);
        },
];
