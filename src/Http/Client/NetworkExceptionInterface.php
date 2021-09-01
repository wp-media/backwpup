<?php

namespace Inpsyde\BackWPup\Http\Client;

/**
 * Thrown when the request cannot be completed because of network issues.
 *
 * There is no response object as this exception is thrown when no response has been received.
 */
interface NetworkExceptionInterface extends ClientExceptionInterface
{
    /**
     * Returns the request.
     *
     * @return \Psr\Http\Message\RequestInterface
     */
    public function getRequest();
}
