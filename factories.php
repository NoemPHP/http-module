<?php

declare(strict_types=1);

use FastRoute\RouteCollector;
use Invoker\InvokerInterface;
use Noem\Container\Attribute\Alias;
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
use Noem\Http\HttpRequestListener;
use Noem\Http\MiddlewareAttributeLoader;
use Noem\Http\ResponseEmitter;
use Noem\Http\RouteAwareMiddlewareCollection;
use Noem\Http\RouteLoader;
use Noem\State\StateMachineInterface;
use Noem\StateMachineModule\Attribute\Action;
use Noem\StateMachineModule\Attribute\OnEntry;
use Noem\StateMachineModule\Attribute\State;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7Server\ServerRequestCreator;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Relay\Relay;

use function FastRoute\simpleDispatcher;

return [
    Psr17Factory::class=>
        #[Alias(RequestFactoryInterface::class)]
        #[Alias(ResponseFactoryInterface::class)]
        #[Alias(ServerRequestFactoryInterface::class)]
        #[Alias(StreamFactoryInterface::class)]
        #[Alias(UriFactoryInterface::class)]
        fn()=>new Psr17Factory(),
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
//            #[Id('http.request-handler')] RequestHandlerInterface $requestHandler,
            RouteAwareMiddlewareCollection $middlewareCollection,
            ResponseEmitter                $emitter
        ) => function (
            HttpRequestEvent $requestEvent
        ) use ($middlewareCollection, $emitter) {
            $request = $requestEvent->request();
            $queue = $middlewareCollection->forRequest($request);
            $queue[] = new Middlewares\RequestHandler();
            $requestHandler = new Relay($queue);
            $requestEvent->setResponse($requestHandler->handle($request));
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
    'http.route-loader' => function (InvokerInterface $i, Container $c) {
        $routeIds = $c->getIdsWithAttribute(Route::class);

        return new RouteLoader($i, $c, ...$routeIds);
    },
    'http.content-type' =>
        #[Middleware(path: '/.*')]
        fn() => new Middlewares\ContentType(),
    'http.payload' =>
        #[Middleware(path: '/.*')]
        fn() => new Middlewares\JsonPayload(),
    'http.fast-route' =>
        #[Middleware(path: '/.*', priority: 999)]
        fn(
            #[Id('http.route-loader')] $loader
        ): Middlewares\FastRoute => new Middlewares\FastRoute(simpleDispatcher($loader)),
    Middlewares\ContentType::class =>
        #[Middleware(path: '/.*', priority: 0)]
        fn() => new Middlewares\ContentType(),
    //TODO: Shouldn't the container resolve this without the definition?
    MiddlewareAttributeLoader::class => fn(Container $c) => new MiddlewareAttributeLoader($c),
    RouteAwareMiddlewareCollection::class => function (MiddlewareAttributeLoader $attributeLoader) {
        return new RouteAwareMiddlewareCollection(
            ...$attributeLoader->load()
        );
    },
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
            #[Id('state-machine')] StateMachineInterface $stateMachine
        ): HttpRequestListener => new HttpRequestListener($stateMachine),

];
