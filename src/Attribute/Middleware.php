<?php

namespace Noem\Http\Attribute;

use Attribute;
use Nette\Schema\Expect;
use Nette\Schema\Processor;

/**
 * @property string path
 * @property string method
 * @property int priority
 */
#[Attribute(Attribute::TARGET_METHOD | Attribute::TARGET_FUNCTION | Attribute::IS_REPEATABLE)]
class Middleware
{
    private array $props;


    public function __construct(...$props)
    {
        $props = array_merge([
            'method' => 'GET',
            'priority' => 50
        ], $props);
        $schema = Expect::structure([
            'path' => Expect::string(),
            'method' => Expect::string(),
            'priority' => Expect::int(),
        ]);
        $processor = new Processor();
        $processor->process($schema, $props);
        $this->props = $props;
    }

    public function __get(string $key)
    {
        if (!array_key_exists($key, $this->props)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Property "%s" does not exist on %s',
                    $key,
                    self::class
                )
            );
        }
        return $this->props[$key];
    }

    public function toArray(): array
    {
        return $this->props;
    }
}
