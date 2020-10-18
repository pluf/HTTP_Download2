<?php

namespace Pluf\Tests\Mocks;

class InvokableTest
{
    public static $CalledCount = 0;

    public function __invoke()
    {
        return static::$CalledCount++;
    }
}
