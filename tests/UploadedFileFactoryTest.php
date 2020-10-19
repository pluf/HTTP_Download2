<?php
namespace Pluf\Tests;

use Interop\Http\Factory\UploadedFileFactoryTestCase;
use Pluf\Http\StreamFactory;
use Pluf\Http\UploadedFileFactory;
use Psr\Http\Message\StreamInterface;
use InvalidArgumentException;

class UploadedFileFactoryTest extends UploadedFileFactoryTestCase
{

    /**
     *
     * @return UploadedFileFactory
     */
    protected function createUploadedFileFactory()
    {
        return new UploadedFileFactory();
    }

    /**
     *
     * @return StreamInterface
     */
    protected function createStream($content)
    {
        $file = tempnam(sys_get_temp_dir(), 'Slim_Http_UploadedFileTest_');
        $resource = fopen($file, 'r+');
        fwrite($resource, $content);
        rewind($resource);

        return (new StreamFactory())->createStreamFromResource($resource);
    }

    /**
     * Prophesize a `\Psr\Http\Message\StreamInterface` with a `getMetadata` method prophecy.
     *
     * @param string $argKey
     *            Argument for the method prophecy.
     * @param mixed $returnValue
     *            Return value of the `getMetadata` method.
     *            
     * @return StreamInterface
     */
    protected function prophesizeStreamInterfaceWithGetMetadataMethod(string $argKey, $returnValue): StreamInterface
    {
        $streamProphecy = $this->prophesize(StreamInterface::class);

        /**
         *
         * @noinspection PhpUndefinedMethodInspection
         */
        $streamProphecy->getMetadata($argKey)
            ->willReturn($returnValue)
            ->shouldBeCalled();

        /** @var StreamInterface $stream */
        $stream = $streamProphecy->reveal();

        return $stream;
    }

    /**
     */
    public function testCreateUploadedFileWithInvalidUri()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('File is not readable.');

        // Prophesize a `\Psr\Http\Message\StreamInterface` with a `getMetadata` method prophecy.
        $streamProphecy = $this->prophesize(StreamInterface::class);

        /**
         *
         * @noinspection PhpUndefinedMethodInspection
         */
        $streamProphecy->getMetadata('uri')
            ->willReturn(null)
            ->shouldBeCalled();

        /** @var StreamInterface $stream */
        $stream = $streamProphecy->reveal();

        $this->factory->createUploadedFile($stream);
    }

    /**
     */
    public function testCreateUploadedFileWithNonReadableFile()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('File is not readable.');

        // Prophesize a `\Psr\Http\Message\StreamInterface` with a `getMetadata` and `isReadable` method prophecies.
        $streamProphecy = $this->prophesize(StreamInterface::class);

        /**
         *
         * @noinspection PhpUndefinedMethodInspection
         */
        $streamProphecy->getMetadata('uri')
            ->willReturn('non-readable')
            ->shouldBeCalled();

        /**
         *
         * @noinspection PhpUndefinedMethodInspection
         */
        $streamProphecy->isReadable()
            ->willReturn(false)
            ->shouldBeCalled();

        /** @var StreamInterface $stream */
        $stream = $streamProphecy->reveal();

        $this->factory->createUploadedFile($stream);
    }
}
