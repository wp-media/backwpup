<?php

namespace Inpsyde\BackWPup\Http\Client;

/**
 * Exception for when a request failed.
 */
interface RequestExceptionInterface extends ClientExceptionInterface
{
    /**
     * Returns the request.
     *
     * @return \Psr\Http\Message\RequestInterface
     */
    public function getRequest();
}
