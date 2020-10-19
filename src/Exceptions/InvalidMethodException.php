<?php
namespace Pluf\Http\Exceptions;

use InvalidArgumentException;
use Psr\Http\Message\ServerRequestInterface;

class InvalidMethodException extends InvalidArgumentException
{

    /**
     *
     * @var ServerRequestInterface
     */
    protected $request;

    /**
     *
     * @param ServerRequestInterface $request
     * @param string $method
     */
    public function __construct(ServerRequestInterface $request, $method)
    {
        $this->request = $request;
        parent::__construct(sprintf('Unsupported HTTP method "%s" provided', $method));
    }

    /**
     *
     * @return ServerRequestInterface
     */
    public function getRequest()
    {
        return $this->request;
    }
}