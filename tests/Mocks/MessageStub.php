<?php
namespace Pluf\Tests\Mocks;

use Pluf\Http\Message;
use Pluf\Http\HeadersInterface;
use Psr\Http\Message\StreamInterface;

class MessageStub extends Message
{

    /**
     * Protocol version
     *
     * @var string
     */
    public $protocolVersion;

    /**
     * Headers
     *
     * @var HeadersInterface
     */
    public $headers;

    /**
     * Body object
     *
     * @var StreamInterface
     */
    public $body;
}
