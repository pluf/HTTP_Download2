<?php
namespace Pluf\Tests\Mocks;

class CallableTest
{
    public static $CalledCount = 0;

    public static $CalledContainer = null;

    public function __construct($container = null)
    {
        static::$CalledContainer = $container;
    }

    public function toCall()
    {
        return static::$CalledCount++;
    }
}
