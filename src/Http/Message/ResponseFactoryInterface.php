<?php

namespace Inpsyde\BackWPup\Http\Message;

/**
 * A factory to create a response.
 */
interface ResponseFactoryInterface
{
    /**
     * Create a new response.
     *
     * @param int    $code         HTTP status code; defaults to 200
     * @param string $reasonPhrase reason phrase to associate with status code
     *                             in generated response
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function createResponse($code = 200, $reasonPhrase = '');
}
