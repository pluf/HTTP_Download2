<?php
namespace Pluf\Tests\Mocks;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class MiddlewareStub
{

    public function run(RequestInterface $request, ResponseInterface $response, $next)
    {
        return $response;
    }
}
