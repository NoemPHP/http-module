<?php

declare(strict_types=1);

namespace Noem\Http\Attribute;

use Attribute;

/**
 *
 */
#[Attribute(Attribute::TARGET_METHOD | Attribute::TARGET_FUNCTION | Attribute::IS_REPEATABLE)]
class Route
{

    public const GET = 1 << 0;
    public const POST = 1 << 1;
    public const PUT = 1 << 2;
    public const DELETE = 1 << 3;
    public const PATCH = 1 << 4;

    public function __construct(public string $path, public int $method = Route::GET)
    {
    }
}
