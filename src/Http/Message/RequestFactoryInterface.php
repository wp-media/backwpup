<?php

namespace Inpsyde\BackWPup\Http\Message;

/**
 * Factory for creating requests.
 */
interface RequestFactoryInterface
{
    /**
     * Create a new request.
     *
     * @param string              $method the HTTP method associated with the request
     * @param UriInterface|string $uri    The URI associated with the request. If
     *                                    the value is a string, the factory MUST create a UriInterface
     *                                    instance based on it.
     *
     * @return \Psr\Http\Message\RequestInterface
     */
    public function createRequest($method, $uri);
}
