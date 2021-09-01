<?php

namespace Inpsyde\BackWPup\Http\Client;

use Psr\Http\Message\RequestInterface;

/**
 * Interface for HTTP client.
 */
interface ClientInterface
{
    /**
     * Sends a PSR-7 request and returns a PSR-7 response.
     *
     * @return \Psr\Http\Message\ResponseInterface
     *
     * @throws ClientExceptionInterface if an error happens while processing the request
     */
    public function sendRequest(RequestInterface $request);
}
