<?php
namespace Pluf\Tests;

use PHPUnit\Framework\TestCase;
use Pluf\Http\Environment;
use Pluf\Http\Request;
use Pluf\Http\Exceptions\InvalidMethodException;

class ExceptionsTest extends TestCase
{

    /**
     * Invalid method test
     *
     * @test
     */
    public function invalidExceptionTest()
    {
        $request = Request::createFromEnvironment(new Environment());
        $e = new InvalidMethodException($request, 'POST');
        self::assertEquals($request, $e->getRequest());
    }
}

