<?php
namespace Pluf\Http;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;

class ResponseFactory implements ResponseFactoryInterface
{

    /**
     *
     * {@inheritdoc}
     */
    public function createResponse(int $code = StatusCode::STATUS_OK, string $reasonPhrase = ''): ResponseInterface
    {
        $res = new Response($code);

        if ($reasonPhrase !== '') {
            $res = $res->withStatus($code, $reasonPhrase);
        }

        return $res;
    }
}
