<?php

namespace Pluf\Http;

use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileFactoryInterface;
use Psr\Http\Message\UploadedFileInterface;
use InvalidArgumentException;

class UploadedFileFactory implements UploadedFileFactoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function createUploadedFile(
        StreamInterface $stream,
        ?int $size = null,
        int $error = UPLOAD_ERR_OK,
        ?string $clientFilename = null,
        ?string $clientMediaType = null
    ): UploadedFileInterface {
        $file = $stream->getMetadata('uri');

        if (!is_string($file) || !$stream->isReadable()) {
            throw new InvalidArgumentException('File is not readable.');
        }

        if ($size === null) {
            $size = $stream->getSize();
        }

        return new UploadedFile($stream, $clientFilename, $clientMediaType, $size, $error);
    }
}
