<?php

declare(strict_types=1);

use FastRoute\RouteCollector;
use Invoker\InvokerInterface;
use Noem\Container\Attribute\Id;
use Noem\Container\Attribute\Tag;
use Noem\Container\Attribute\Tagged;
use Noem\Container\Container;
use Noem\Container\ContainerHtmlRenderer;
use Noem\Http\Attribute\Route;
use Noem\Http\RouteLoader;
use Noem\State\StateMachine;
use Noem\StateMachineModule\Attribute\Action;
use Noem\StateMachineModule\Attribute\OnEntry;
use Noem\StateMachineModule\Attribute\State;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7Server\ServerRequestCreator;
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
            \Noem\Http\ResponseEmitter $emitter
        ) => function (
            ServerRequestInterface $request
        ) use ($requestHandler, $emitter) {
            $emitter->emit($requestHandler->handle($request));
        },
    'http.request' =>
        function () {
            if (PHP_SAPI === 'cli') {
                throw new Exception('Cannot create global Request object in a CLI call');
            }
            $creator = new ServerRequestCreator(...array_fill(0, 4, new Psr17Factory()));

            return $creator->fromGlobals();
        },
    'http.home-route' =>
        #[Tag('route')]
        fn(Container $container) => #[Route('/')] function (ServerRequestInterface $r) use ($container) {
            $containerContents = (new ContainerHtmlRenderer($container->report()))->render();
            echo <<<HTML
<html>
    <head></head>
    <body>
        <h1>Hello World, Noem here</h1>
        <style>
        thead,
tfoot {
    background-color: #3f87a6;
    color: #fff;
}

tbody {
    background-color: #e4f0f5;
}

caption {
    padding: 10px;
    caption-side: bottom;
}

table {
    border-collapse: collapse;
    border: 2px solid rgb(200, 200, 200);
    letter-spacing: 1px;
    font-family: sans-serif;
    font-size: .8rem;
}

td,
th {
    border: 1px solid rgb(190, 190, 190);
    padding: 5px 10px;
}

td {
    text-align: center;
}
</style>
        {$containerContents}
    </body>
</html>
HTML;
//            var_dump(func_get_args());
        },
    RouteLoader::class => fn(InvokerInterface $i) => new RouteLoader($i),
    'http.fast-route' =>
        #[Tag('http.middleware')]
        fn(RouteLoader $loader, #[Tagged('route')] callable ...$routes) => new Middlewares\FastRoute(
            simpleDispatcher(fn(RouteCollector $r) => $loader($r, ...$routes))
        ),
    'http.request-handler' => function (
        #[Tagged('http.middleware')] MiddlewareInterface ...$handlers
    ): RequestHandlerInterface {
        $middlewares = [
//            new Middlewares\Emitter(),
            ...$handlers,
            new Middlewares\ContentType(),
            new Middlewares\RequestHandler()
        ];
        return new Relay($middlewares);
    },
    'request-listener' =>
        #[Tag('event-listener')]
        fn(
            #[Id('state-machine')] StateMachine $m
        ) => function (
            ServerRequestInterface $request,
        ) use ($m) {
            $m->action($request);
        }
];
