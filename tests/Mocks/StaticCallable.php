<?php
namespace Pluf\Tests\Mocks;

use Pluf\Http\Response;
use Psr\Http\Message\ServerRequestInterface;

class StaticCallable
{

    public static function run(ServerRequestInterface $request, Response $response, $next)
    {
        $response->write('In1');

        /** @var Response $response */
        $response = $next($request, $response);
        $response->write('Out1');

        return $response;
    }
}
