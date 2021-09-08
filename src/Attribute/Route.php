<?php

declare(strict_types=1);

namespace Noem\Http\Attribute;

use Attribute;
use Noem\Http\Method;

/**
 *
 */
#[Attribute(Attribute::TARGET_METHOD | Attribute::TARGET_FUNCTION | Attribute::IS_REPEATABLE)]
class Route
{


    public function __construct(public string $path, public int $method = Method::GET)
    {
    }
}
