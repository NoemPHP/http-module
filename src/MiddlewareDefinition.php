<?php

declare(strict_types=1);

namespace Noem\Http;

use Psr\Http\Server\MiddlewareInterface;

class MiddlewareDefinition
{
    public function __construct(
        public MiddlewareInterface $middleware,
        public string $path,
        public array $methods,
        public int $priority,
    ) {
    }
}
