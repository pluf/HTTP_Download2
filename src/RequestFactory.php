<?php
namespace Pluf\Http;

use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;
use Psr\Http\Message\UriInterface;
use InvalidArgumentException;

class RequestFactory implements RequestFactoryInterface
{

    /**
     *
     * @var StreamFactoryInterface|StreamFactory
     */
    protected $streamFactory;

    /**
     *
     * @var UriFactoryInterface|UriFactory
     */
    protected $uriFactory;

    /**
     *
     * @param StreamFactoryInterface|null $streamFactory
     * @param UriFactoryInterface|null $uriFactory
     */
    public function __construct(?StreamFactoryInterface $streamFactory = null, ?UriFactoryInterface $uriFactory = null)
    {
        if (! isset($streamFactory)) {
            $streamFactory = new StreamFactory();
        }

        if (! isset($uriFactory)) {
            $uriFactory = new UriFactory();
        }

        $this->streamFactory = $streamFactory;
        $this->uriFactory = $uriFactory;
    }

    /**
     *
     * {@inheritdoc}
     */
    public function createRequest(string $method, $uri): RequestInterface
    {
        if (is_string($uri)) {
            $uri = $this->uriFactory->createUri($uri);
        }

        if (! $uri instanceof UriInterface) {
            throw new InvalidArgumentException('Parameter 2 of RequestFactory::createRequest() must be a string or a compatible UriInterface.');
        }

        $body = $this->streamFactory->createStream();

        return new Request($method, $uri, new Headers(), [], [], $body);
    }
}
