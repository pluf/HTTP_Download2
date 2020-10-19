<?php
namespace Pluf\Tests;

use Interop\Http\Factory\RequestFactoryTestCase;
use Pluf\Http\RequestFactory;
use Pluf\Http\UriFactory;
use Psr\Http\Message\UriInterface;
use InvalidArgumentException;
use stdClass;

class RequestFactoryTest extends RequestFactoryTestCase
{

    /**
     *
     * @return RequestFactory
     */
    protected function createRequestFactory()
    {
        return new RequestFactory();
    }

    /**
     *
     * @param string $uri
     * @return UriInterface
     */
    protected function createUri($uri)
    {
        return (new UriFactory())->createUri($uri);
    }

    /**
     */
    public function testCreateRequestThrowsExceptionWithInvalidUri()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Parameter 2 of RequestFactory::createRequest() must be a string' . ' or a compatible UriInterface.');

        $factory = $this->createRequestFactory();

        $factory->createRequest('GET', new stdClass());
    }
}
