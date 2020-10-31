<?php
namespace Pluf\Http;

use Psr\Http\Message\ResponseInterface;

class HttpResponseEmitter
{

    /**
     * Assert either that no headers been sent or the output buffer contains no content.
     *
     * @throws \RuntimeException
     *
     * @return void
     */
    protected function assertNoPreviousOutput(): void
    {
        $file = $line = null;

        if (headers_sent($file, $line)) {
            throw new \RuntimeException(sprintf('Unable to emit response: Headers already sent in file %s on line %s. ' . 'This happens if echo, print, printf, print_r, var_dump, var_export or similar statement that writes to the output buffer are used.', $file, (string) $line));
        }

        if (ob_get_level() > 0 && ob_get_length() > 0) {
            throw new \RuntimeException('Output has been emitted previously; cannot emit response.');
        }
    }

    /**
     * Emit the status line.
     *
     * Emits the status line using the protocol version and status code from
     * the response; if a reason phrase is availble, it, too, is emitted.
     *
     * It's important to mention that, in order to prevent PHP from changing
     * the status code of the emitted response, this method should be called
     * after `emitBody()`
     *
     * @param \Psr\Http\Message\ResponseInterface $response
     *
     * @return void
     */
    protected function emitStatusLine(ResponseInterface $response): void
    {
        $statusCode = $response->getStatusCode();

        header(\vsprintf('HTTP/%s %d%s', [
            $response->getProtocolVersion(),
            $statusCode,
            \rtrim(' ' . $response->getReasonPhrase())
        ]), true, $statusCode);
    }

    /**
     * Emit response headers.
     *
     * Loops through each header, emitting each; if the header value
     * is an array with multiple values, ensures that each is sent
     * in such a way as to create aggregate headers (instead of replace
     * the previous).
     *
     * @param \Psr\Http\Message\ResponseInterface $response
     *
     * @return void
     */
    protected function emitHeaders(ResponseInterface $response): void
    {
        $statusCode = $response->getStatusCode();

        foreach ($response->getHeaders() as $header => $values) {
            $name = $this->toWordCase($header);
            $first = $name !== 'Set-Cookie';

            foreach ($values as $value) {
                header(sprintf('%s: %s', $name, $value), $first, $statusCode);

                $first = false;
            }
        }
    }

    /**
     * Converts header names to wordcase.
     *
     * @param string $header
     *
     * @return string
     */
    protected function toWordCase(string $header): string
    {
        $filtered = \str_replace('-', ' ', $header);
        $filtered = \ucwords($filtered);

        return \str_replace(' ', '-', $filtered);
    }

    /**
     * Flushes output buffers and closes the connection to the client,
     * which ensures that no further output can be sent.
     *
     * @return void
     */
    protected function closeConnection(): void
    {
        if (! \in_array(\PHP_SAPI, [
            'cli',
            'phpdbg'
        ], true)) {
            self::closeOutputBuffers(0, true);
        }

        if (\function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        }
    }

    /**
     * Emit a response.
     *
     * Emits a response, including status line, headers, and the message body,
     * according to the environment.
     *
     * Implementations of this method may be written in such a way as to have
     * side effects, such as usage of header() or pushing output to the
     * output buffer.
     *
     * Implementations MAY raise exceptions if they are unable to emit the
     * response; e.g., if headers have already been sent.
     *
     * @param \Psr\Http\Message\ResponseInterface $response
     *
     * @return void
     */
    public function emit(ResponseInterface $response): void
    {
        $this->assertNoPreviousOutput();

        $this->emitHeaders($response);

        // Set the status _after_ the headers, because of PHP's "helpful" behavior with location headers.
        $this->emitStatusLine($response);

        $this->emitBody($response);

        $this->closeConnection();
    }

    /**
     * Sends the message body of the response.
     *
     * @param \Psr\Http\Message\ResponseInterface $response
     */
    private function emitBody(ResponseInterface $response): void
    {
        echo $response->getBody();
    }

    /**
     * Inject the Content-Length header if is not already present.
     *
     * @param \Psr\Http\Message\ResponseInterface $response
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public static function injectContentLength(ResponseInterface $response): ResponseInterface
    {
        // PSR-7 indicates int OR null for the stream size; for null values,
        // we will not auto-inject the Content-Length.
        if (! $response->hasHeader('Content-Length') && $response->getBody()->getSize() !== null) {
            $response = $response->withHeader('Content-Length', (string) $response->getBody()
                ->getSize());
        }

        return $response;
    }

    /**
     * Cleans or flushes output buffers up to target level.
     *
     * Resulting level can be greater than target level if a non-removable buffer has been encountered.
     *
     * @param int $maxBufferLevel
     *            The target output buffering level
     * @param bool $flush
     *            Whether to flush or clean the buffers
     *            
     * @return void
     */
    public static function closeOutputBuffers(int $maxBufferLevel, bool $flush): void
    {
        $status = \ob_get_status(true);
        $level = \count($status);
        $flags = \PHP_OUTPUT_HANDLER_REMOVABLE | ($flush ? \PHP_OUTPUT_HANDLER_FLUSHABLE : \PHP_OUTPUT_HANDLER_CLEANABLE);

        while ($level -- > $maxBufferLevel && (bool) ($s = $status[$level]) && ($s['del'] ?? ! isset($s['flags']) || $flags === ($s['flags'] & $flags))) {
            if ($flush) {
                \ob_end_flush();
            } else {
                \ob_end_clean();
            }
        }
    }
}
