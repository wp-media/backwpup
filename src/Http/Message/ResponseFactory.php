<?php

namespace Inpsyde\BackWPup\Http\Message;

use GuzzleHttp\Psr7\Response;

/**
 * Factory for creating responses.
 *
 * Uses GuzzleHttp\Psr7 objects for implementation.
 */
class ResponseFactory implements ResponseFactoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function createResponse($code = 200, $reasonPhrase = '')
    {
        return new Response($code, [], null, '1.1', $reasonPhrase);
    }
}
