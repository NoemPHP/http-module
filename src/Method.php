<?php

namespace Noem\Http;

class Method
{
    public const GET = 1 << 0;
    public const POST = 1 << 1;
    public const PUT = 1 << 2;
    public const DELETE = 1 << 3;
    public const PATCH = 1 << 4;
    public const HEAD = 1 << 5;

    public static function isGet(int $methodFlags): bool
    {
        return ($methodFlags & self::GET) == self::GET;
    }

    public static function isPost(int $methodFlags): bool
    {
        return ($methodFlags & self::POST) == self::POST;
    }

    public static function isPut(int $methodFlags): bool
    {
        return ($methodFlags & self::PUT) == self::PUT;
    }

    public static function isDelete(int $methodFlags): bool
    {
        return ($methodFlags & self::DELETE) == self::DELETE;
    }

    public static function isPatch(int $methodFlags): bool
    {
        return ($methodFlags & self::PATCH) == self::PATCH;
    }

    public static function isHead(int $methodFlags): bool
    {
        return ($methodFlags & self::HEAD) == self::HEAD;
    }

    public static function arrayFromFlags(int $methodFlags): \Generator
    {
        if (self::isGet($methodFlags)) {
            yield 'GET';
        }
        if (self::isPost($methodFlags)) {
            yield 'POST';
        }
        if (self::isPut($methodFlags)) {
            yield 'PUT';
        }
        if (self::isDelete($methodFlags)) {
            yield 'DELETE';
        }
        if (self::isPatch($methodFlags)) {
            yield 'PATCH';
        }
        if (self::isHead($methodFlags)) {
            yield 'HEAD';
        }
    }
}
