<?php

namespace Noem\Http;

use Noem\State\StateMachineInterface;

class HttpRequestListener
{
    public function __construct(private StateMachineInterface $stateMachine)
    {
    }

    public function __invoke(HttpRequestEvent $request)
    {
        $this->stateMachine->action($request);
    }
}
