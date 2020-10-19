<?php

namespace Pluf\Tests;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Pluf\Http\Headers;
use Pluf\Http\Stream;
use Pluf\Tests\Mocks\MessageStub;
use InvalidArgumentException;

class MessageTest extends TestCase
{
    public function testGetProtocolVersion()
    {
        $message = new MessageStub();
        $message->protocolVersion = '1.0';

        $this->assertEquals('1.0', $message->getProtocolVersion());
    }

    public function testWithProtocolVersion()
    {
        $message = new MessageStub();
        $clone = $message->withProtocolVersion('1.0');

        $this->assertEquals('1.0', $clone->protocolVersion);
    }

    /**
     */
    public function testWithProtocolVersionInvalidThrowsException()
    {
        $this->expectException(InvalidArgumentException::class);

        $message = new MessageStub();
        $message->withProtocolVersion('3.0');
    }

    public function testGetHeaders()
    {
        $headers = new Headers();
        $headers->addHeader('X-Foo', 'one');
        $headers->addHeader('X-Foo', 'two');
        $headers->addHeader('X-Foo', 'three');

        $message = new MessageStub();
        $message->headers = $headers;

        $shouldBe = [
            'X-Foo' => [
                'one',
                'two',
                'three',
            ],
        ];

        $this->assertEquals($shouldBe, $message->getHeaders());
    }

    public function testHasHeader()
    {
        $headers = new Headers();
        $headers->addHeader('X-Foo', 'one');

        $message = new MessageStub();
        $message->headers = $headers;

        $this->assertTrue($message->hasHeader('X-Foo'));
        $this->assertFalse($message->hasHeader('X-Bar'));
    }

    public function testGetHeaderLine()
    {
        $headers = new Headers();
        $headers->addHeader('X-Foo', 'one');
        $headers->addHeader('X-Foo', 'two');
        $headers->addHeader('X-Foo', 'three');

        $message = new MessageStub();
        $message->headers = $headers;

        $this->assertEquals('one,two,three', $message->getHeaderLine('X-Foo'));
        $this->assertEquals('', $message->getHeaderLine('X-Bar'));
    }

    public function testGetHeader()
    {
        $headers = new Headers();
        $headers->addHeader('X-Foo', 'one');
        $headers->addHeader('X-Foo', 'two');
        $headers->addHeader('X-Foo', 'three');

        $message = new MessageStub();
        $message->headers = $headers;

        $this->assertEquals(['one', 'two', 'three'], $message->getHeader('X-Foo'));
        $this->assertEquals([], $message->getHeader('X-Bar'));
    }

    public function testWithHeader()
    {
        $headers = new Headers();
        $headers->addHeader('X-Foo', 'one');
        $message = new MessageStub();
        $message->headers = $headers;
        $clone = $message->withHeader('X-Foo', 'bar');

        $this->assertEquals('bar', $clone->getHeaderLine('X-Foo'));
    }

    public function testWithAddedHeader()
    {
        $headers = new Headers();
        $headers->addHeader('X-Foo', 'one');
        $message = new MessageStub();
        $message->headers = $headers;
        $clone = $message->withAddedHeader('X-Foo', 'two');

        $this->assertEquals('one,two', $clone->getHeaderLine('X-Foo'));
    }

    public function testWithoutHeader()
    {
        $headers = new Headers();
        $headers->addHeader('X-Foo', 'one');
        $headers->addHeader('X-Bar', 'two');
        $response = new MessageStub();
        $response->headers = $headers;
        $clone = $response->withoutHeader('X-Foo');
        $shouldBe = [
            'X-Bar' => ['two'],
        ];

        $this->assertEquals($shouldBe, $clone->getHeaders());
    }

    /**
     * @doesNotPerformAssertions
     */
    public function testWithoutHeaderByIncompatibleStringWithRFC()
    {
        $headers = new Headers();
        $response = new MessageStub();
        $response->headers = $headers;
        $response->withoutHeader('<incompatible with RFC');
    }

    public function testGetBody()
    {
        $body = $this->getBody();
        $message = new MessageStub();
        $message->body = $body;

        $this->assertSame($body, $message->getBody());
    }

    public function testWithBody()
    {
        $body = $this->getBody();
        $body2 = $this->getBody();
        $message = new MessageStub();
        $message->body = $body;
        $clone = $message->withBody($body2);

        $this->assertSame($body, $message->body);
        $this->assertSame($body2, $clone->body);
    }

    /**
     * @return MockObject|Stream
     */
    protected function getBody()
    {
        return $this
            ->getMockBuilder(Stream::class)
            ->disableOriginalConstructor()
            ->getMock();
    }
}
